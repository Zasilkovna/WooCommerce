<?php
/**
 * Class DetailCommonLogic.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity\Order;
use Packetery\Module;
use Packetery\Module\ShippingMethod;
use Packetery\Nette;

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
	 * Context resolver.
	 *
	 * @var Module\ContextResolver
	 */
	private $contextResolver;

	/**
	 * Request.
	 *
	 * @var Nette\Http\Request
	 */
	private $request;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Constructor.
	 *
	 * @param Module\ContextResolver $contextResolver Context resolver.
	 * @param Nette\Http\Request     $request         Request.
	 * @param Repository             $orderRepository Order repository.
	 */
	public function __construct(
		Module\ContextResolver $contextResolver,
		Nette\Http\Request $request,
		Repository $orderRepository
	) {
		$this->contextResolver = $contextResolver;
		$this->request         = $request;
		$this->orderRepository = $orderRepository;
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

		$this->order = $this->orderRepository->getById( $orderId, true );

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

}
