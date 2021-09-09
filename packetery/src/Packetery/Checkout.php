<?php
/**
 * Packeta plugin class for checkout.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery;

use Packetery\Carrier\Repository;
use Packetery\Options\Provider;
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
			'name'     => 'packetery_point_id',
			'required' => true,
		),
		'name'                 => array(
			'name'     => 'packetery_point_name',
			'required' => true,
		),
		'city'                 => array(
			'name'     => 'packetery_point_city',
			'required' => true,
		),
		'zip'                  => array(
			'name'     => 'packetery_point_zip',
			'required' => true,
		),
		'street'               => array(
			'name'     => 'packetery_point_street',
			'required' => true,
		),
		'pickupPointType'      => array(
			'name'     => 'packetery_point_type',
			'required' => true,
		),
		'carrierId'            => array(
			'name'     => 'packetery_carrier_id',
			'required' => false,
		),
		'carrierPickupPointId' => array(
			'name'     => 'packetery_point_carrier_id',
			'required' => false,
		),
		'url'                  => array(
			'name'     => 'packetery_point_url',
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
	 * @param string $chosenMethod Shipping rate id.
	 * @param array  $matches Array with carrier id.
	 *
	 * @return bool
	 */
	public function isPickupPointMethod( string $chosenMethod, array &$matches ): bool {
		if ( strpos( $chosenMethod, 'zpoint' ) !== false ) {
			$matches[1] = 'packeta';

			return true;
		}

		if ( preg_match( '/^' . self::CARRIER_PREFIX . '(\d+)$/', $chosenMethod, $matches ) ) {
			$isPickupPoints = $this->carrierRepository->getIsPickupPoints( (int) $matches[1] );

			return ( '1' === $isPickupPoints );
		}

		return false;
	}

	/**
	 * Check if chosen shipping rate is bound with Packeta pickup points
	 *
	 * @return bool
	 */
	public function isPickupPointOrder(): bool {
		$chosenMethod = $this->getChosenMethod();
		$matches      = [];

		return $this->isPickupPointMethod( $chosenMethod, $matches );
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
		$matches      = [];
		$chosenMethod = $this->getChosenMethod();
		if ( $this->isPickupPointMethod( $chosenMethod, $matches ) ) {
			$carriers = $matches[1];
		}

		$this->latte_engine->render(
			PACKETERY_PLUGIN_DIR . '/template/checkout/widget_button.latte',
			array(
				'language' => $language,
				'country'  => $country,
				'weight'   => number_format( $weight, 3 ),
				'carriers' => $carriers,
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
			$fields['shipping'][ $attr['name'] ] = [
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
	 * Saves pickup point information to order
	 *
	 * @param int $order_id Order id.
	 */
	public function updateOrderMeta( int $order_id ): void {
		if ( $this->isPickupPointOrder() ) {
			$post = $this->httpRequest->getPost();
			if ( ! wp_verify_nonce( $post['_wpnonce'], self::NONCE_ACTION ) ) {
				wp_nonce_ays( '' );
			}
			foreach ( self::$pickup_point_attrs as $attr ) {
				if ( isset( $post[ $attr['name'] ] ) ) {
					update_post_meta( $order_id, $attr['name'], $post[ $attr['name'] ] );
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
		if ( ! empty( $chosenMethod ) && $chosenMethod === $codMethod ) {
			$isCod = true;
		}

		$customRates = [];
		foreach ( $carrierOptions as $optionId => $options ) {
			if ( is_array( $options ) ) {
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
}
