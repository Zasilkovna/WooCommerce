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
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Nette;
use WC_Order;

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

		try {
			$this->order = $this->orderRepository->getById( $orderId );
		} catch ( InvalidCarrierException $invalidCarrierException ) {
			return null;
		}

		return $this->order;
	}

	/**
	 * Gets WC_Order from context.
	 *
	 * @return WC_Order|null
	 */
	public function getWcOrder(): ?WC_Order {
		$orderId = $this->getOrderId();
		if ( null === $orderId ) {
			return null;
		}

		return $this->orderRepository->getWcOrderById( $orderId );
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
		if ( null !== $idParam && Module\Helper::isHposEnabled() ) {
			return (int) $idParam;
		}

		return $post->ID;
	}
}
