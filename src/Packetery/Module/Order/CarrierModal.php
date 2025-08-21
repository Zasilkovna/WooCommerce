<?php
/**
 * Class CarrierModal.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Latte;
use Packetery\Module\Carrier;
use Packetery\Module\Carrier\CarrierOptionsFactory;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\MessageManager;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Nette\Forms;
use Packetery\Nette\Forms\Controls\SubmitButton;
use RuntimeException;
use WC_Order_Item_Shipping;

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
	 * @var Latte\Engine
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
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * Carrier options factory.
	 *
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * @var MessageManager
	 */
	private $messageManager;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		Latte\Engine $latteEngine,
		DetailCommonLogic $detailCommonLogic,
		CarrierModalFormFactory $carrierModalFormFactory,
		Repository $orderRepository,
		Carrier\EntityRepository $carrierRepository,
		OptionsProvider $optionsProvider,
		CarrierOptionsFactory $carrierOptionsFactory,
		MessageManager $messageManager,
		WpAdapter $wpAdapter
	) {
		$this->latteEngine             = $latteEngine;
		$this->detailCommonLogic       = $detailCommonLogic;
		$this->carrierModalFormFactory = $carrierModalFormFactory;
		$this->orderRepository         = $orderRepository;
		$this->carrierRepository       = $carrierRepository;
		$this->optionsProvider         = $optionsProvider;
		$this->carrierOptionsFactory   = $carrierOptionsFactory;
		$this->messageManager          = $messageManager;
		$this->wpAdapter               = $wpAdapter;
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
			$this->detailCommonLogic->isPacketeryOrder() === false ||
			$this->canBeDisplayed() === false
		) {
			return;
		}

		$form              = $this->carrierModalFormFactory->create(
			$this->getCarrierOptionsByCountry(),
			$this->getCurrentCarrier()
		);
		$form->onSuccess[] = [ $this, 'onFormSuccess' ];

		$submitButton = $form['submit'];
		if ( $submitButton instanceof SubmitButton && $submitButton->isSubmittedBy() ) {
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
		if ( $orderId === null ) {
			throw new RuntimeException( 'Packeta: Failed to process carrier change, new carrier id ' . $newCarrierId );
		}

		$order = $this->detailCommonLogic->getOrder();
		if ( $order !== null && $order->getCarrier()->getId() !== $newCarrierId ) {
			$deletionSuccess = $this->orderRepository->delete( (int) $order->getNumber() );
			if ( $deletionSuccess === false ) {
				$this->messageManager->flash_message(
					(string) $this->wpAdapter->__( 'An error occurred while deleting the order. More details in WC log.', 'packeta' ),
					MessageManager::TYPE_ERROR
				);
			}
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
		if ( $newCarrier === null ) {
			throw new RuntimeException( 'Packeta: Failed to get instance of carrier with id ' . $newCarrierId );
		}
		$updatedRowCount = $this->orderRepository->saveData(
			[
				'id'         => $orderId,
				'carrier_id' => $newCarrierId,
			]
		);
		if ( $updatedRowCount === false ) {
			$this->messageManager->flash_message(
				(string) $this->wpAdapter->__( 'An error occurred while saving the order. More details in WC log.', 'packeta' ),
				MessageManager::TYPE_ERROR
			);
		}

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
		if ( $wcOrderId === null ) {
			return [];
		}

		$wcOrder = $this->orderRepository->getWcOrderById( $wcOrderId );
		if ( $wcOrder === null ) {
			return [];
		}

		$shippingCountry = ModuleHelper::getWcOrderCountry( $wcOrder );
		if ( $shippingCountry === '' ) {
			return [];
		}

		$carriers = $this->carrierRepository->getByCountryIncludingNonFeed( $shippingCountry, false );

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
		if ( $carrierOptions === [] ) {
			return false;
		}

		if ( $this->optionsProvider->isWcCarrierConfigEnabled() ) {
			return false;
		}

		$order = $this->detailCommonLogic->getOrder();

		return ( $order === null || $order->getPacketId() === null );
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
		if ( $order === null ) {
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
		if ( $order === null ) {
			return;
		}
		$shippingItems = $order->get_items( 'shipping' );
		if ( is_array( $shippingItems ) && count( $shippingItems ) > 0 ) {
			$firstItem = reset( $shippingItems );
			if ( $firstItem instanceof WC_Order_Item_Shipping ) {
				$firstItem->set_method_title( $carrierTitle );
				$firstItem->save();
				$order->calculate_totals();
				$order->save();
			}
		}
	}
}
