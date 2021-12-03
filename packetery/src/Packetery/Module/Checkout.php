<?php
/**
 * Packeta plugin class for checkout.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module;

use Packetery\Module\Carrier\Repository;
use Packetery\Module\Options\Provider;
use Packetery\Module\Order\Entity;
use PacketeryLatte\Engine;
use PacketeryNette\Http\Request;

/**
 * Class Checkout
 *
 * @package Packetery
 */
class Checkout {

	public const CARRIER_PREFIX = 'packetery_carrier_';
	private const NONCE_ACTION  = 'packetery_checkout';

	/**
	 * Pickup point attributes configuration.
	 *
	 * @var array[]
	 */
	public static $pickup_point_attrs = array(
		'id'                   => array(
			'name'     => Entity::META_POINT_ID,
			'required' => true,
		),
		'name'                 => array(
			'name'     => Entity::META_POINT_NAME,
			'required' => true,
		),
		'city'                 => array(
			'name'     => Entity::META_POINT_CITY,
			'required' => true,
		),
		'zip'                  => array(
			'name'     => Entity::META_POINT_ZIP,
			'required' => true,
		),
		'street'               => array(
			'name'     => Entity::META_POINT_STREET,
			'required' => true,
		),
		'pickupPointType'      => array(
			'name'     => Entity::META_POINT_TYPE,
			'required' => true,
		),
		'carrierId'            => array(
			'name'     => Entity::META_CARRIER_ID,
			'required' => false,
		),
		'carrierPickupPointId' => array(
			'name'     => Entity::META_POINT_CARRIER_ID,
			'required' => false,
		),
		'url'                  => array(
			'name'     => Entity::META_POINT_URL,
			'required' => true,
		),
	);

	/**
	 * PacketeryLatte engine
	 *
	 * @var Engine
	 */
	private $latte_engine;

	/**
	 * Options provider.
	 *
	 * @var Provider Options provider.
	 */
	private $options_provider;

	/**
	 * Carrier repository.
	 *
	 * @var Repository Carrier repository.
	 */
	private $carrierRepository;

	/**
	 * Http request.
	 *
	 * @var Request Http request.
	 */
	private $httpRequest;

	/**
	 * Checkout constructor.
	 *
	 * @param Engine     $latte_engine PacketeryLatte engine.
	 * @param Provider   $options_provider Options provider.
	 * @param Repository $carrierRepository Carrier repository.
	 * @param Request    $httpRequest Http request.
	 */
	public function __construct( Engine $latte_engine, Provider $options_provider, Repository $carrierRepository, Request $httpRequest ) {
		$this->latte_engine      = $latte_engine;
		$this->options_provider  = $options_provider;
		$this->carrierRepository = $carrierRepository;
		$this->httpRequest       = $httpRequest;
	}

	/**
	 * Checks if chosen carrier has pickup points and sets carrier id in provided array.
	 *
	 * @param string|null $carrierId Carrier id.
	 *
	 * @return bool
	 */
	public function isPickupPointCarrier( ?string $carrierId ): bool {
		if ( null === $carrierId ) {
			return false;
		}
		if ( Repository::INTERNAL_PICKUP_POINTS_ID === $carrierId ) {
			return true;
		}

		return $this->carrierRepository->hasPickupPoints( (int) $carrierId );
	}

	/**
	 * Check if chosen shipping rate is bound with Packeta pickup points
	 *
	 * @return bool
	 */
	public function isPickupPointOrder(): bool {
		$chosenMethod = $this->getChosenMethod();
		$carrierId    = $this->getCarrierId( $chosenMethod );

		return $this->isPickupPointCarrier( $carrierId );
	}

	/**
	 * Renders widget button and information about chosen pickup point
	 */
	public function renderWidgetButton(): void {
		$language = substr( get_locale(), 0, 2 );

		$country = $this->getCustomerCountry();
		if ( ! $country ) {
			$this->latte_engine->render( PACKETERY_PLUGIN_DIR . '/template/checkout/error_country.latte' );
			return;
		}

		$weight = $this->getCartWeightKg();

		$carriers     = '';
		$chosenMethod = $this->getChosenMethod();
		$carrierId    = $this->getCarrierId( $chosenMethod );
		if ( $this->isPickupPointCarrier( $carrierId ) ) {
			$carriers = $carrierId;
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/widget_button.latte',
			array(
				'language' => $language,
				'country'  => $country,
				'weight'   => number_format( $weight, 3 ),
				'carriers' => $carriers,
				'logo'     => plugin_dir_url( PACKETERY_PLUGIN_DIR . '/packetery.php' ) . 'public/packeta-symbol.png',
			)
		);
	}

	/**
	 * Renders main checkout script
	 */
	public function render_after_checkout_form(): void {
		$app_identity = 'woocommerce-' . get_bloginfo( 'version' ) . '-' . WC_VERSION . '-' . Plugin::VERSION;

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/checkout_script.latte',
			array(
				'app_identity'       => $app_identity,
				'pickup_point_attrs' => self::$pickup_point_attrs,
				'packetery_api_key'  => $this->options_provider->get_api_key(),
				'carrierPrefix'      => self::CARRIER_PREFIX,
				'carriers'           => $this->carrierRepository->getAllIncludingZpoints(),
			)
		);
	}

	/**
	 * Adds fields to checkout page to save the values later
	 *
	 * @link https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
	 *
	 * @param array $fields Fields before adding.
	 *
	 * @return array
	 */
	public static function add_pickup_point_fields( array $fields ): array {
		foreach ( self::$pickup_point_attrs as $attr ) {
			$fields['billing'][ $attr['name'] ] = [
				'type'              => 'text',
				'required'          => false,
				// For older WooCommerce. See woocommerce_form_field function.
				'custom_attributes' => [ 'style' => 'display: none;' ],
			];
		}

		return $fields;
	}

	/**
	 * Checks if all pickup point attributes are set, sets an error otherwise.
	 */
	public function validatePickupPointData(): void {
		if ( $this->isPickupPointOrder() ) {
			$post = $this->httpRequest->getPost();
			if ( ! wp_verify_nonce( $post['_wpnonce'], self::NONCE_ACTION ) ) {
				wp_nonce_ays( '' );
			}

			$error          = false;
			$required_attrs = array_filter(
				array_combine(
					array_column( self::$pickup_point_attrs, 'name' ),
					array_column( self::$pickup_point_attrs, 'required' )
				)
			);
			foreach ( $required_attrs as $attr => $required ) {
				$attr_value = null;
				if ( isset( $post[ $attr ] ) ) {
					$attr_value = $post[ $attr ];
				}
				if ( ! $attr_value ) {
					$error = true;
				}
			}
			$carrierId = null;
			if ( isset( $post['carrier_id'] ) ) {
				$carrierId = $post['carrier_id'];
			}
			$pointCarrierId = null;
			if ( isset( $post['point_carrier_id'] ) ) {
				$pointCarrierId = $post['point_carrier_id'];
			}
			if ( $carrierId && ! $pointCarrierId ) {
				$error = true;
			}
			if ( ! $carrierId && $pointCarrierId ) {
				$error = true;
			}
			if ( $error ) {
				wc_add_notice( __( 'Pick up point is not chosen.', 'packetery' ), 'error' );
			}
		}
	}

	/**
	 * Saves pickup point and other Packeta information to order.
	 *
	 * @param int $orderId Order id.
	 */
	public function updateOrderMeta( int $orderId ): void {
		$chosenMethod = $this->getChosenMethod();
		if ( false === $this->isPacketeryOrder( $chosenMethod ) ) {
			return;
		}
		$post = $this->httpRequest->getPost();
		if ( empty( $post[ Entity::META_CARRIER_ID ] ) ) {
			$carrierId = $this->getCarrierId( $chosenMethod );
			if ( $carrierId ) {
				update_post_meta( $orderId, Entity::META_CARRIER_ID, $carrierId );
			}
		}
		if ( $this->isPickupPointOrder() ) {
			if ( ! wp_verify_nonce( $post['_wpnonce'], self::NONCE_ACTION ) ) {
				wp_nonce_ays( '' );
			}
			foreach ( self::$pickup_point_attrs as $attr ) {
				if (
					isset( $post[ $attr['name'] ] ) &&
					( Entity::META_CARRIER_ID !== $attr['name'] || $post[ $attr['name'] ] )
				) {
					update_post_meta( $orderId, $attr['name'], $post[ $attr['name'] ] );
				}
			}
		}
	}

	/**
	 * Renders nonce field
	 */
	public static function render_nonce_field() {
		wp_nonce_field( self::NONCE_ACTION );
	}

	/**
	 * Registers Packeta checkout hooks
	 */
	public function register_hooks(): void {
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'renderWidgetButton' ) );
		add_action( 'woocommerce_after_checkout_form', array( $this, 'render_after_checkout_form' ) );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'add_pickup_point_fields' ) );
		add_action( 'woocommerce_after_order_notes', array( __CLASS__, 'render_nonce_field' ) );
		add_action( 'woocommerce_checkout_process', array( $this, 'validatePickupPointData' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'updateOrderMeta' ) );
		add_action( 'woocommerce_review_order_before_shipping', array( $this, 'updateShippingRates' ), 10, 2 );
	}

	/**
	 * Updates shipping rates cost based on cart properties.
	 */
	public function updateShippingRates(): void {
		$customRates = $this->getShippingRates();

		$packages = WC()->shipping()->get_packages();
		foreach ( $packages as $i => $package ) {
			if ( ! empty( $package['rates'] ) ) {
				foreach ( $package['rates'] as $key => $rate ) {
					if ( isset( $customRates[ $rate->get_id() ] ) ) {
						$rate->set_cost( $customRates[ $rate->get_id() ]['cost'] );
						WC()->shipping->packages[ $i ]['rates'][ $key ] = $rate;
					}
				}
			}
		}
	}

	/**
	 * Gets customer country from WC cart.
	 *
	 * @return string
	 */
	public function getCustomerCountry(): string {
		$country = strtolower( WC()->customer->get_shipping_country() );
		if ( ! $country ) {
			$country = strtolower( WC()->customer->get_billing_country() );
		}

		return $country;
	}

	/**
	 * Gets cart contents weight in kg.
	 *
	 * @return float|int
	 */
	public function getCartWeightKg() {
		$weight = WC()->cart->cart_contents_weight;
		$weight = wc_get_weight( $weight, 'kg' );

		return $weight;
	}

	/**
	 * Prepare shipping rates based on cart properties.
	 *
	 * @return array
	 */
	public function getShippingRates(): array {
		$customerCountry   = $this->getCustomerCountry();
		$availableCarriers = $this->carrierRepository->getByCountryIncludingZpoints( $customerCountry );
		$carrierOptions    = [];
		foreach ( $availableCarriers as $carrier ) {
			$optionId                    = self::CARRIER_PREFIX . $carrier['id'];
			$carrierOptions[ $optionId ] = get_option( $optionId );
		}

		$cartPrice = (float) WC()->cart->get_subtotal();

		$cartWeight = $this->getCartWeightKg();

		$isCod        = false;
		$codMethod    = $this->options_provider->getCodPaymentMethod();
		$chosenMethod = WC()->session->get( 'chosen_payment_method' );
		if ( null !== $codMethod && ! empty( $chosenMethod ) && $chosenMethod === $codMethod ) {
			$isCod = true;
		}

		$customRates = [];
		foreach ( $carrierOptions as $optionId => $options ) {
			if ( is_array( $options ) && true === $options['active'] ) {
				$cost = $this->getRateCost( $options, $cartPrice, $cartWeight, $isCod );
				if ( null !== $cost ) {
					$customRates[ $optionId ] = [
						'label'    => $options['name'],
						'id'       => $optionId,
						'cost'     => $cost,
						'taxes'    => '',
						'calc_tax' => 'per_order',
					];
				}
			}
		}

		return $customRates;
	}

	/**
	 * Computes custom rate cost for carrier using cart contents.
	 *
	 * @param array     $carrierOptions Carrier options.
	 * @param float     $cartPrice Price.
	 * @param float|int $cartWeight Weight.
	 * @param bool      $isCod COD.
	 *
	 * @return int|float|null
	 */
	private function getRateCost( array $carrierOptions, float $cartPrice, $cartWeight, bool $isCod ) {
		$cost = null;
		if ( $carrierOptions['free_shipping_limit'] && $cartPrice >= $carrierOptions['free_shipping_limit'] ) {
			$cost = 0;
		} else {
			foreach ( $carrierOptions['weight_limits'] as $weightLimit ) {
				if ( $cartWeight <= $weightLimit['weight'] ) {
					$cost = $weightLimit['price'];
					break;
				}
			}
			if ( $isCod && is_numeric( $cost ) ) {
				foreach ( $carrierOptions['surcharge_limits'] as $weightLimit ) {
					if ( $cartPrice <= $weightLimit['order_price'] ) {
						$cost += $weightLimit['surcharge'];
						break;
					}
				}
			}
		}

		return $cost;
	}

	/**
	 * Get chosen shipping rate id.
	 *
	 * @return string
	 */
	private function getChosenMethod(): string {
		$chosenMethods = wc_get_chosen_shipping_method_ids();
		if ( ! empty( $chosenMethods[0] ) ) {
			return $chosenMethods[0];
		}

		return '';
	}

	/**
	 * Gets carrier id from chosen shipping method.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return string|null
	 */
	public function getCarrierId( string $chosenMethod ): ?string {
		if ( ! $this->isPacketeryOrder( $chosenMethod ) ) {
			return null;
		}

		$carrierId = str_replace( self::CARRIER_PREFIX, '', $chosenMethod );
		if ( strpos( $carrierId, 'zpoint' ) === 0 ) {
			return Repository::INTERNAL_PICKUP_POINTS_ID;
		}

		return $carrierId;
	}


	/**
	 * Checks if chosen shipping method is one of packetery.
	 *
	 * @param string $chosenMethod Chosen shipping method.
	 *
	 * @return bool
	 */
	private function isPacketeryOrder( string $chosenMethod ): bool {
		return ( strpos( $chosenMethod, self::CARRIER_PREFIX ) === 0 );
	}
}
