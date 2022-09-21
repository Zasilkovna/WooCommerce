<?php
/**
 * Class PacketCanceller
 *
 * @package Packetery\Api
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Soap;
use Packetery\Core\Entity;
use Packetery\Core\Log;
use Packetery\Module\MessageManager;
use Packetery\Module\Plugin;
use PacketeryNette\Http\Request;


/**
 * Class PacketActionsBase
 *
 * @package Packetery\Api
 */
class PacketActionsBase {

	public const PARAM_ORDER_ID           = 'order_id';
	public const PARAM_REDIRECT_TO        = 'packetery_redirect_to';
	public const REDIRECT_TO_ORDER_GRID   = 'order-grid';
	public const REDIRECT_TO_ORDER_DETAIL = 'order-detail';
	public const ACTION_CANCEL_PACKET     = 'cancel_packet';
	public const ACTION_SUBMIT_PACKET     = 'submit_packet';

	/**
	 * SOAP API Client.
	 *
	 * @var Soap\Client SOAP API Client.
	 */
	protected $soapApiClient;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	protected $orderRepository;

	/**
	 * ILogger.
	 *
	 * @var Log\ILogger
	 */
	protected $logger;

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
	 * Order ID.
	 *
	 * @var int
	 */
	public $orderId;

	/**
	 * Order entity.
	 *
	 * @var Entity\Order
	 */
	public $order;

	/**
	 * Packet action.
	 *
	 * @var string action
	 */
	public $action;

	/**
	 * Order ID.
	 *
	 * @var string logAction
	 */
	public $logAction;

	/**
	 * Constructor.
	 *
	 * @param Soap\Client    $soapApiClient   Soap client API.
	 * @param Repository     $orderRepository Order repository.
	 * @param Log\ILogger    $logger          Logger.
	 * @param Request        $request         Request.
	 * @param MessageManager $messageManager  Message manager.
	 */
	public function __construct( Soap\Client $soapApiClient, Repository $orderRepository, Log\ILogger $logger, Request $request, MessageManager $messageManager ) {
		$this->soapApiClient   = $soapApiClient;
		$this->orderRepository = $orderRepository;
		$this->logger          = $logger;
		$this->request         = $request;
		$this->messageManager  = $messageManager;
	}

	/**
	 * Process actions.
	 *
	 * @return void
	 */
	public function processAction(): void {
		$redirectTo    = $this->request->getQuery( self::PARAM_REDIRECT_TO );
		$this->orderId = $this->getOrderId();
		if ( null !== $this->orderId ) {
			$this->order = $this->orderRepository->getById( $this->orderId );
		}

		if ( null === $this->order ) {
			$record          = new Log\Record();
			$record->action  = $this->getLogAction();
			$record->status  = Log\Record::STATUS_ERROR;
			$record->orderId = null;
			$record->title   = __( 'Packet cancel error', 'packeta' );
			$record->params  = [
				'referer'      => (string) $this->request->getReferer(),
				'errorMessage' => 'Order not found',
			];

			$this->logger->add( $record );

			$this->messageManager->flash_message( __( 'Order not found', 'packeta' ), MessageManager::TYPE_ERROR );
			$this->redirectTo( $redirectTo, $this->order );

			return;
		}

		if ( 1 !== wp_verify_nonce( $this->request->getQuery( Plugin::PARAM_NONCE ), self::createNonceAction( $this->getAction(), $this->order->getNumber() ) ) ) {
			$this->messageManager->flash_message( __( 'Link has expired. Please try again.', 'packeta' ), MessageManager::TYPE_ERROR );
			$this->redirectTo( $redirectTo, $this->order );
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
	protected function redirectTo( string $redirectTo, ?Entity\Order $order ): void {
		if ( self::REDIRECT_TO_ORDER_GRID === $redirectTo ) {
			$redirectLink = add_query_arg(
				[
					'post_type' => 'shop_order',
				],
				admin_url( 'edit.php' )
			);

			if ( wp_safe_redirect( $redirectLink ) ) {
				exit;
			}
		}

		if ( self::REDIRECT_TO_ORDER_DETAIL === $redirectTo && null !== $order ) {
			$redirectLink = add_query_arg(
				[
					'post_type' => 'shop_order',
					'post'      => $order->getNumber(),
					'action'    => 'edit',
				],
				admin_url( 'post.php' )
			);

			if ( wp_safe_redirect( $redirectLink ) ) {
				exit;
			}
		}
	}

	/**
	 * Gets order ID.
	 *
	 * @return int|null
	 */
	protected function getOrderId(): ?int {
		$orderId = $this->request->getQuery( self::PARAM_ORDER_ID );
		if ( is_numeric( $orderId ) ) {
			return (int) $orderId;
		}

		return null;
	}

	/**
	 * Gets packet action
	 *
	 * @return string
	 */
	public function getAction(): string {
		return $this->action;
	}

	/**
	 * Sets packet action
	 *
	 * @param string $action Packet action.
	 */
	public function setAction( string $action ): void {
		$this->action = $action;
	}

	/**
	 * Gets action string for logger
	 *
	 * @return string
	 */
	public function getLogAction(): string {
		return $this->logAction;
	}

	/**
	 * Sets action string for logger
	 *
	 * @param string $logAction Action for logger.
	 */
	public function setLogAction( string $logAction ): void {
		$this->logAction = $logAction;
	}
}
