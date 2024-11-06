<?php
/**
 * Class CarrierModal.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Latte\Engine;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Carrier\WcSettingsConfig;
use Packetery\Module\ModuleHelper;
use Packetery\Nette\Forms;
use RuntimeException;

/**
 * Class CarrierModal.
 *
 * @package Packetery
 */
class CarrierModal {

	public const MODAL_ID = 'wc-packetery-carrier-modal';

	/**
	 * Latte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * Order detail common logic.
	 *
	 * @var DetailCommonLogic
	 */
	private $detailCommonLogic;

	/**
	 * Order repository.
	 *
	 * @var CarrierModalFormFactory
	 */
	private $carrierModalFormFactory;

	/**
	 * Carrier repository.
	 *
	 * @var Carrier\EntityRepository
	 */
	private $carrierRepository;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Native Carrier settings.
	 *
	 * @var WcSettingsConfig
	 */
	private $wcNativeCarrierSettings;

	/**
	 * Carrier options factory.
	 *
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * Constructor.
	 *
	 * @param Engine                   $latteEngine              Latte engine.
	 * @param DetailCommonLogic        $detailCommonLogic        Detail common logic.
	 * @param CarrierModalFormFactory  $carrierModalFormFactory  Carrier Modal form factory.
	 * @param Repository               $orderRepository          Order repository.
	 * @param Carrier\EntityRepository $carrierRepository        Carrier repository.
	 * @param WcSettingsConfig         $wcNativeCarrierSettings  Native Carrier settings.
	 * @param CarrierOptionsFactory    $carrierOptionsFactory    Carrier options factory.
	 */
	public function __construct(
		Engine $latteEngine,
		DetailCommonLogic $detailCommonLogic,
		CarrierModalFormFactory $carrierModalFormFactory,
		Repository $orderRepository,
		Carrier\EntityRepository $carrierRepository,
		WcSettingsConfig $wcNativeCarrierSettings,
		CarrierOptionsFactory $carrierOptionsFactory
	) {
		$this->latteEngine             = $latteEngine;
		$this->detailCommonLogic       = $detailCommonLogic;
		$this->carrierModalFormFactory = $carrierModalFormFactory;
		$this->orderRepository         = $orderRepository;
		$this->carrierRepository       = $carrierRepository;
		$this->wcNativeCarrierSettings = $wcNativeCarrierSettings;
		$this->carrierOptionsFactory   = $carrierOptionsFactory;
	}

	/**
	 * Registers order modal.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_head', [ $this, 'renderCarrierModal' ] );
	}

	/**
	 * Renders modal.
	 *
	 * @return void
	 */
	public function renderCarrierModal(): void {
		if (
			false === $this->detailCommonLogic->isPacketeryOrder() ||
			false === $this->canBeDisplayed()
		) {
			return;
		}

		$form              = $this->carrierModalFormFactory->create(
			$this->getCarrierOptionsByCountry(),
			$this->getCurrentCarrier()
		);
		$form->onSuccess[] = [ $this, 'onFormSuccess' ];

		if ( $form['submit']->isSubmittedBy() ) {
			$form->fireEvents();
		}

		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/order/carrier-modal.latte',
			[
				'id'           => self::MODAL_ID,
				'form'         => $form,
				'translations' => [
					'header'          => __( 'Set carrier', 'packeta' ),
					'closeModalPanel' => __( 'Close modal panel', 'packeta' ),
				],
			]
		);
	}

	/**
	 * On form success.
	 *
	 * @param Forms\Form $form Form.
	 *
	 * @return void
	 *
	 * @throws RuntimeException In case carrier id could not be obtained.
	 */
	public function onFormSuccess( Forms\Form $form ): void {
		$values       = $form->getValues();
		$orderId      = $this->detailCommonLogic->getOrderId();
		$newCarrierId = (string) $values[ CarrierModalFormFactory::FIELD_CARRIER_ID ];
		if ( null === $orderId ) {
			throw new RuntimeException( 'Packeta: Failed to process carrier change, new carrier id ' . $newCarrierId );
		}

		$order = $this->detailCommonLogic->getOrder();
		if ( null !== $order && $order->getCarrier()->getId() !== $newCarrierId ) {
			$this->orderRepository->delete( (int) $order->getNumber() );
		}

		$carrierTitle = $this->createNewCarrierOrder( $orderId, $newCarrierId );
		$this->updateOrderDeliveryTitle( $orderId, $carrierTitle );

		// Without it, the widget cannot be opened and metabox has no values. Needed even in case no change was made.
		if ( wp_safe_redirect( ModuleHelper::getOrderDetailUrl( $orderId ) ) ) {
			exit;
		}
	}

	/**
	 * Saves order stub with new Carrier, if instantiable.
	 *
	 * @param int    $orderId      Order id.
	 * @param string $newCarrierId Carrier id.
	 *
	 * @return string
	 *
	 * @throws RuntimeException In case carrier is not instantiable.
	 */
	private function createNewCarrierOrder( int $orderId, string $newCarrierId ): string {
		$newCarrier = $this->carrierRepository->getAnyById( $newCarrierId );
		if ( null === $newCarrier ) {
			throw new RuntimeException( 'Packeta: Failed to get instance of carrier with id ' . $newCarrierId );
		}
		$this->orderRepository->saveData(
			[
				'id'         => $orderId,
				'carrier_id' => $newCarrierId,
			]
		);

		$options = $this->carrierOptionsFactory->createByCarrierId( $newCarrier->getId() );
		if ( ! $options->hasOptions() ) {
			throw new RuntimeException( 'Missing options for carrier ' . $newCarrier->getId() );
		}

		return $options->getName();
	}

	/**
	 * Gets carrier names by the country of destination.
	 *
	 * @return string[]
	 */
	private function getCarrierOptionsByCountry(): array {
		static $carrierOptions;

		if ( isset( $carrierOptions ) ) {
			return $carrierOptions;
		}

		$wcOrderId = $this->detailCommonLogic->getOrderId();
		if ( null === $wcOrderId ) {
			return [];
		}

		$wcOrder = $this->orderRepository->getWcOrderById( $wcOrderId );
		if ( null === $wcOrder ) {
			return [];
		}

		$shippingCountry = ModuleHelper::getWcOrderCountry( $wcOrder );
		if ( empty( $shippingCountry ) ) {
			return [];
		}

		$carriers = $this->carrierRepository->getByCountryIncludingNonFeed( $shippingCountry );

		$carrierOptions = [];
		foreach ( $carriers as $carrier ) {
			$options = $this->carrierOptionsFactory->createByCarrierId( $carrier->getId() );
			if ( $options->hasOptions() ) {
				$carrierOptions[ $carrier->getId() ] = $options->getName();
			}
		}

		return $carrierOptions;
	}

	/**
	 * There must be two carriers at least and no packet id.
	 *
	 * @return bool
	 */
	public function canBeDisplayed(): bool {
		$carrierOptions = $this->getCarrierOptionsByCountry();
		if ( [] === $carrierOptions ) {
			return false;
		}

		if ( $this->wcNativeCarrierSettings->isActive() ) {
			return false;
		}

		$order = $this->detailCommonLogic->getOrder();

		return ( null === $order || null === $order->getPacketId() );
	}

	/**
	 * Gets metabox HTML with carrier selection button.
	 *
	 * @return string
	 */
	public function getMetaboxHtml(): string {
		return $this->latteEngine->renderToString(
			PACKETERY_PLUGIN_DIR . '/template/order/metabox-carrier.latte',
			[
				'translations' => [
					'setCarrier' => __( 'Set carrier', 'packeta' ),
				],
			]
		);
	}

	/**
	 * Get current order carrier.
	 *
	 * @return string|null
	 */
	private function getCurrentCarrier(): ?string {
		$order = $this->detailCommonLogic->getOrder();
		if ( null === $order ) {
			return null;
		}

		return $order->getCarrier()->getId();
	}

	/**
	 * Update the delivery title of a specific order.
	 *
	 * @param int    $orderId      The ID of the order to update.
	 * @param string $carrierTitle The new shipping title to set.
	 *
	 * @return void
	 */
	private function updateOrderDeliveryTitle( int $orderId, string $carrierTitle ): void {
		$order = $this->orderRepository->getWcOrderById( $orderId );
		if ( null === $order ) {
			return;
		}
		$shippingItems = $order->get_items( 'shipping' );
		if ( count( $shippingItems ) > 0 ) {
			$firstItem = array_shift( $shippingItems );
			$firstItem->set_method_title( $carrierTitle );
			$firstItem->save();
			$order->calculate_totals();
			$order->save();
		}
	}

}
