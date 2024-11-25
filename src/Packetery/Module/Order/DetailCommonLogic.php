<?php
/**
 * Class DetailCommonLogic.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity\Order;
use Packetery\Core\Entity\PickupPoint;
use Packetery\Module;
use Packetery\Module\ContextResolver;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\ShippingMethod;
use Packetery\Nette\Http;

/**
 * Class DetailCommonLogic.
 *
 * @package Packetery
 */
class DetailCommonLogic {

	/**
	 * Order.
	 *
	 * @var Order|null
	 */
	private $order;

	/**
	 * @var ContextResolver
	 */
	private $contextResolver;

	/**
	 * @var Http\Request
	 */
	private $request;

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
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct(
		ContextResolver $contextResolver,
		Http\Request $request,
		Repository $orderRepository,
		OptionsProvider $optionsProvider,
		WcAdapter $wcAdapter
	) {
		$this->contextResolver = $contextResolver;
		$this->request         = $request;
		$this->orderRepository = $orderRepository;
		$this->optionsProvider = $optionsProvider;
		$this->wcAdapter       = $wcAdapter;
	}

	/**
	 * Gets order.
	 *
	 * @return Order|null
	 */
	public function getOrder(): ?Order {
		if ( null !== $this->order ) {
			return $this->order;
		}

		$orderId = $this->getOrderId();
		if ( null === $orderId ) {
			return null;
		}

		$this->order = $this->orderRepository->getByIdWithValidCarrier( $orderId );

		return $this->order;
	}

	/**
	 * Gets order ID.
	 *
	 * @return int|null
	 */
	public function getOrderId(): ?int {
		global $post;

		if ( false === $this->contextResolver->isOrderDetailPage() ) {
			return null;
		}

		$idParam = $this->request->getQuery( 'id' );
		if ( null !== $idParam && Module\ModuleHelper::isHposEnabled() ) {
			return (int) $idParam;
		}

		return (int) $post->ID;
	}

	/**
	 * Checks if Packeta order detail is displayed.
	 *
	 * @return bool
	 */
	public function isPacketeryOrder(): bool {
		$orderId = $this->getOrderId();
		if ( null === $orderId ) {
			return false;
		}

		$wcOrder = $this->orderRepository->getWcOrderById( $orderId );
		if ( null === $wcOrder ) {
			return false;
		}

		if ( ! $wcOrder->has_shipping_method( ShippingMethod::PACKETERY_METHOD_ID ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Tells if pickup point info should be displayed.
	 *
	 * @return bool
	 */
	public function shouldDisplayPickupPointInfo(): bool {
		return (
			! $this->optionsProvider->replaceShippingAddressWithPickupPointAddress() ||
			$this->wcAdapter->shipToBillingAddressOnly()
		);
	}

	/**
	 * Determines if a Packeta order should be displayed.
	 */
	public function shouldHidePacketaInfo( Order $order ): bool {
		$isPickupPointInfoVisible = $this->shouldDisplayPickupPointInfo() && $order->getPickupPoint() instanceof PickupPoint;

		return ( ! $isPickupPointInfoVisible ) && false === $order->isExported();
	}
}
