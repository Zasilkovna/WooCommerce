<?php
/**
 * Class CarrierModal.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity;
use Packetery\Latte\Engine;
use Packetery\Module\Carrier;
use Packetery\Module\Helper;
use Packetery\Nette\Forms;
use RuntimeException;
use function add_query_arg;
use function get_admin_url;
use function wp_safe_redirect;

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
	 * Constructor.
	 *
	 * @param Engine                   $latteEngine             Latte engine.
	 * @param DetailCommonLogic        $detailCommonLogic       Detail common logic.
	 * @param CarrierModalFormFactory  $carrierModalFormFactory Carrier Modal form factory.
	 * @param Repository               $orderRepository         Order repository.
	 * @param Carrier\EntityRepository $carrierRepository       Carrier repository.
	 */
	public function __construct(
		Engine $latteEngine,
		DetailCommonLogic $detailCommonLogic,
		CarrierModalFormFactory $carrierModalFormFactory,
		Repository $orderRepository,
		Carrier\EntityRepository $carrierRepository
	) {
		$this->latteEngine             = $latteEngine;
		$this->detailCommonLogic       = $detailCommonLogic;
		$this->carrierModalFormFactory = $carrierModalFormFactory;
		$this->orderRepository         = $orderRepository;
		$this->carrierRepository       = $carrierRepository;
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

		$form              = $this->carrierModalFormFactory->create( $this->getCarriersByCountry(), $this->getCurrentCarrier() );
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
		if ( null === $order ) {
			$this->createNewCarrierOrder( $orderId, $newCarrierId );
		}

		if ( $order->getCarrier()->getId() !== $newCarrierId ) {
			$this->orderRepository->delete( (int) $order->getNumber() );
			$this->createNewCarrierOrder( $orderId, $newCarrierId );
		}

		// Without it, the widget cannot be opened and metabox has no values. Needed even in case no change was made.
		if ( wp_safe_redirect(
			add_query_arg(
				[
					'page'   => 'wc-orders',
					'action' => 'edit',
					'id'     => $orderId,
				],
				get_admin_url( null, 'admin.php' )
			)
		) ) {
			exit;
		}
	}

	/**
	 * Saves order stub with new Carrier, if instantiable.
	 *
	 * @param int    $orderId Order id.
	 * @param string $newCarrierId Carrier id.
	 *
	 * @return void
	 *
	 * @throws RuntimeException In case carrier is not instantiable.
	 */
	private function createNewCarrierOrder( int $orderId, string $newCarrierId ): void {
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
	}

	/**
	 * Gets Carriers by the country of destination.
	 *
	 * @return Entity\Carrier[]
	 */
	private function getCarriersByCountry(): array {
		static $carriers;

		if ( isset( $carriers ) ) {
			return $carriers;
		}

		$wcOrderId = $this->detailCommonLogic->getOrderid();
		if ( null === $wcOrderId ) {
			return [];
		}

		$wcOrder = $this->orderRepository->getWcOrderById( $wcOrderId );
		if ( null === $wcOrder ) {
			return [];
		}

		$shippingCountry = Helper::getWcOrderCountry( $wcOrder );
		if ( empty( $shippingCountry ) ) {
			return [];
		}

		$carriers = $this->carrierRepository->getByCountryIncludingNonFeed( $shippingCountry );

		foreach ( $carriers as $key => $carrier ) {
			$options = Carrier\Options::createByCarrierId( $carrier->getId() );
			if ( ! $options->hasOptions() ) {
				unset( $carriers[ $key ] );
			}
		}

		return $carriers;
	}

	/**
	 * There must be two carriers at least and no packet id.
	 *
	 * @return bool
	 */
	public function canBeDisplayed(): bool {
		$carriers = $this->getCarriersByCountry();
		if ( count( $carriers ) < 2 ) {
			return false;
		}

		$order = $this->detailCommonLogic->getOrder();

		return ( null === $order || null === $order->getPacketId() );
	}

	/**
	 * Renders metabox with carrier selection button.
	 *
	 * @return void
	 */
	public function renderInMetabox(): void {
		$this->latteEngine->render(
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

}
