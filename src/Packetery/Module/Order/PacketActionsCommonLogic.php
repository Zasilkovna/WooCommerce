<?php
/**
 * Class PacketCanceller
 *
 * @package Packetery\Module\Order
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Entity;
use Packetery\Module\ModuleHelper;
use Packetery\Module\MessageManager;
use Packetery\Module\Plugin;
use Packetery\Nette\Http\Request;


/**
 * Class PacketActionsBrain
 *
 * @package Packetery\Module\Order
 */
class PacketActionsCommonLogic {

	public const PARAM_ORDER_ID             = 'order_id';
	public const PARAM_PACKET_ID            = 'packet_id';
	public const PARAM_REDIRECT_TO          = 'packetery_redirect_to';
	public const PARAM_ORDER_GRID_PARAMS    = 'packetery_order_grid_params';
	public const REDIRECT_TO_ORDER_GRID     = 'order-grid';
	public const REDIRECT_TO_ORDER_DETAIL   = 'order-detail';
	public const ACTION_CANCEL_PACKET       = 'cancel_packet';
	public const ACTION_SUBMIT_PACKET       = 'submit_packet';
	public const ACTION_SUBMIT_PACKET_CLAIM = 'submit_packet_claim';

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	protected $orderRepository;

	/**
	 * Request.
	 *
	 * @var Request
	 */
	protected $request;

	/**
	 * Message manager.
	 *
	 * @var MessageManager
	 */
	protected $messageManager;

	/**
	 * Constructor.
	 *
	 * @param Repository     $orderRepository Order repository.
	 * @param Request        $request         Request.
	 * @param MessageManager $messageManager  Message manager.
	 */
	public function __construct(
		Repository $orderRepository,
		Request $request,
		MessageManager $messageManager
	) {
		$this->orderRepository = $orderRepository;
		$this->request         = $request;
		$this->messageManager  = $messageManager;
	}

	/**
	 * Process actions.
	 *
	 * @param string       $action Action.
	 * @param Entity\Order $order Order.
	 *
	 * @return void
	 */
	public function checkAction( string $action, Entity\Order $order ): void {
		$redirectTo = $this->request->getQuery( self::PARAM_REDIRECT_TO );

		if ( 1 !== wp_verify_nonce( $this->request->getQuery( Plugin::PARAM_NONCE ), self::createNonceAction( $action, $order->getNumber() ) ) ) {
			$this->messageManager->flash_message( __( 'Link has expired. Please try again.', 'packeta' ), MessageManager::TYPE_ERROR );
			$this->redirectTo( $redirectTo, $order );
		}
	}

	/**
	 * Creates nonce action name.
	 *
	 * @param string $action      Action.
	 * @param string $orderNumber Order number.
	 *
	 * @return string
	 */
	public static function createNonceAction( string $action, string $orderNumber ): string {
		return $action . '_' . $orderNumber;
	}

	/**
	 * Redirects.
	 *
	 * @param string            $redirectTo Redirect to.
	 * @param Entity\Order|null $order      Order.
	 *
	 * @return void
	 */
	public function redirectTo( string $redirectTo, ?Entity\Order $order = null ): void {
		if ( self::REDIRECT_TO_ORDER_GRID === $redirectTo ) {
			$queryVars = [];
			parse_str( $this->request->getQuery( self::PARAM_ORDER_GRID_PARAMS ) ?? '', $queryVars );

			if ( wp_safe_redirect( ModuleHelper::getOrderGridUrl( $queryVars ) ) ) {
				exit;
			}
		}

		if (
			self::REDIRECT_TO_ORDER_DETAIL === $redirectTo &&
			null !== $order &&
			wp_safe_redirect( ModuleHelper::getOrderDetailUrl( (int) $order->getNumber() ) )
		) {
			exit;
		}
	}

	/**
	 * Gets order ID.
	 *
	 * @return int|null
	 */
	private function getOrderId(): ?int {
		$orderId = $this->request->getQuery( self::PARAM_ORDER_ID );
		if ( is_numeric( $orderId ) ) {
			return (int) $orderId;
		}

		return null;
	}

	/**
	 * Gets order.
	 *
	 * @return Entity\Order|null
	 */
	public function getOrder(): ?Entity\Order {
		$orderId = $this->getOrderId();
		if ( null !== $orderId ) {
			return $this->orderRepository->getById( $orderId, true );
		}

		return null;
	}
}
