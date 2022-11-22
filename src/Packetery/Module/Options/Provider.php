<?php
/**
 * Class Provider
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

use Packetery\Core\Entity\PacketStatus;
use Packetery\Module\Order\PacketSynchronizer;

/**
 * Class Provider
 *
 * @package Packetery
 */
class Provider {

	const OPTION_NAME_PACKETERY                 = 'packetery';
	const OPTION_NAME_PACKETERY_SYNC            = 'packetery_sync';
	const OPTION_NAME_PACKETERY_AUTO_SUBMISSION = 'packetery_auto_submission';

	const DEFAULT_VALUE_PACKETA_LABEL_FORMAT        = 'A6 on A4';
	const DEFAULT_VALUE_CARRIER_LABEL_FORMAT        = self::DEFAULT_VALUE_PACKETA_LABEL_FORMAT;
	const MAX_STATUS_SYNCING_PACKETS_DEFAULT        = 100;
	const MAX_DAYS_OF_PACKET_STATUS_SYNCING_DEFAULT = 14;
	const FORCE_PACKET_CANCEL_DEFAULT               = true;
	const PACKET_AUTO_SUBMISSION_ALLOWED_DEFAULT    = false;
	const WIDGET_AUTO_OPEN_DEFAULT                  = false;
	const ORDER_STATUS_AUTO_CHANGE_DEFAULT          = false;
	const AUTO_ORDER_STATUS_DEFAULT                 = 'wc-processing';
	const ORDER_STATUS_AUTO_CHANGE_FOR_AUTO_SUBMIT_DEFAULT = false;

	/**
	 *  Options data.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Sync data.
	 *
	 * @var array
	 */
	private $syncData;

	/**
	 * Auto submission data.
	 *
	 * @var array
	 */
	private $autoSubmissionData;

	/**
	 * Provider constructor.
	 */
	public function __construct() {
		$data = get_option( self::OPTION_NAME_PACKETERY );
		if ( ! $data ) {
			$data = array();
		}

		$syncData = get_option( self::OPTION_NAME_PACKETERY_SYNC );
		if ( ! $syncData ) {
			$syncData = [];
		}

		$autoSubmissionData = get_option( self::OPTION_NAME_PACKETERY_AUTO_SUBMISSION );
		if ( ! $autoSubmissionData ) {
			$autoSubmissionData = [];
		}

		$this->data               = $data;
		$this->syncData           = $syncData;
		$this->autoSubmissionData = $autoSubmissionData;
	}

	/**
	 * Casts data to array.
	 *
	 * @param string $optionName Option name of settings.
	 *
	 * @return array Data.
	 */
	public function data_to_array( ?string $optionName = null ): array {
		if ( self::OPTION_NAME_PACKETERY === $optionName ) {
			return $this->data;
		}

		if ( self::OPTION_NAME_PACKETERY_SYNC === $optionName ) {
			return $this->syncData;
		}

		if ( self::OPTION_NAME_PACKETERY_AUTO_SUBMISSION === $optionName ) {
			return $this->autoSubmissionData;
		}

		if ( null === $optionName ) {
			return [
				self::OPTION_NAME_PACKETERY      => $this->data,
				self::OPTION_NAME_PACKETERY_SYNC => $this->syncData,
				self::OPTION_NAME_PACKETERY_AUTO_SUBMISSION => $this->autoSubmissionData,
			];
		}
	}

	/**
	 * Tells if provider has any data,
	 *
	 * @param string $optionName Option name of settings.
	 *
	 * @return bool Has any data.
	 */
	public function has_any( string $optionName ): bool {
		return ! empty( $this->data_to_array( $optionName ) );
	}

	/**
	 *  Gets content from options array.
	 *
	 * @param string $key Options array key.
	 *
	 * @return mixed|null Content.
	 */
	private function get( string $key ) {
		return ( $this->data[ $key ] ?? null );
	}

	/**
	 * API key dynamically crafted from API password.
	 *
	 * @return string|null Content.
	 */
	public function get_api_key(): ?string {
		return $this->get( 'api_key' );
	}

	/**
	 * API password from client section.
	 *
	 * @return string|null Content.
	 */
	public function get_api_password(): ?string {
		return $this->get( 'api_password' );
	}

	/**
	 * Sender.
	 *
	 * @return string|null Content.
	 */
	public function get_sender(): ?string {
		return $this->get( 'sender' );
	}

	/**
	 * Carrier label format.
	 *
	 * @return string Content.
	 */
	public function get_carrier_label_format(): string {
		return $this->get( 'carrier_label_format' ) ?? self::DEFAULT_VALUE_CARRIER_LABEL_FORMAT;
	}

	/**
	 * Packeta label format.
	 *
	 * @return string Content.
	 */
	public function get_packeta_label_format(): string {
		return $this->get( 'packeta_label_format' ) ?? self::DEFAULT_VALUE_PACKETA_LABEL_FORMAT;
	}

	/**
	 * Does user allow label emailing?
	 *
	 * @return bool|null Content.
	 */
	public function get_allow_label_emailing(): ?bool {
		return (bool) $this->get( 'allow_label_emailing' );
	}

	/**
	 * Which payment rate id COD?
	 *
	 * @return string|null Content.
	 */
	public function getCodPaymentMethod(): ?string {
		$value = $this->get( 'cod_payment_method' );
		if ( ! $value ) {
			return null;
		}

		return $value;
	}

	/**
	 * Returns the location of the widget button in the cart
	 *
	 * @return string|null
	 */
	public function getCheckoutWidgetButtonLocation(): ?string {
		$value = $this->get( 'checkout_widget_button_location' );
		if ( ! $value ) {
			return null;
		}

		return $value;
	}

	/**
	 * Order packaging weight.
	 *
	 * @return float
	 */
	public function getPackagingWeight(): float {
		return (float) $this->get( 'packaging_weight' );
	}

	/**
	 * Order default weight enabled.
	 *
	 * @return bool
	 */
	public function isDefaultWeightEnabled(): bool {
		return (bool) $this->get( 'default_weight_enabled' );
	}

	/**
	 * Order default weight.
	 *
	 * @return float
	 */
	public function getDefaultWeight(): float {
		if ( $this->get( 'default_weight' ) === null ) {
			return 0.0;
		}
		return (float) $this->get( 'default_weight' );
	}

	/**
	 * Max syncing packets.
	 *
	 * @return int
	 */
	public function getMaxStatusSyncingPackets(): int {
		$value = ( $this->syncData['max_status_syncing_packets'] ?? null );
		if ( is_numeric( $value ) ) {
			return (int) $value;
		}

		return self::MAX_STATUS_SYNCING_PACKETS_DEFAULT;
	}

	/**
	 * Max days of packet status syncing.
	 *
	 * @return int
	 */
	public function getMaxDaysOfPacketStatusSyncing(): int {
		$value = ( $this->syncData['max_days_of_packet_status_syncing'] ?? null );
		if ( is_numeric( $value ) ) {
			return (int) $value;
		}

		return self::MAX_DAYS_OF_PACKET_STATUS_SYNCING_DEFAULT;
	}

	/**
	 * Status syncing order statuses.
	 *
	 * @return array
	 */
	public function getStatusSyncingOrderStatuses(): array {
		$value = ( $this->syncData['status_syncing_order_statuses'] ?? null );
		if ( is_array( $value ) ) {
			return $value;
		}

		return [];
	}

	/**
	 * Status syncing order statuses.
	 *
	 * @return array
	 */
	public function getExistingStatusSyncingOrderStatuses(): array {
		$statuses = $this->getStatusSyncingOrderStatuses();
		$choices  = array_column( Page::getOrderStatusesChoiceData(), 'key' );

		return array_intersect( $statuses, $choices );
	}

	/**
	 * Status syncing packet statuses.
	 *
	 * @return array
	 */
	public function getStatusSyncingPacketStatuses(): array {
		$value = ( $this->syncData['status_syncing_packet_statuses'] ?? null );
		if ( is_array( $value ) ) {
			return $value;
		}

		return array_keys(
			array_filter(
				PacketSynchronizer::getPacketStatuses(),
				static function ( PacketStatus $packetStatus ): bool {
					return true === $packetStatus->hasDefaultSynchronization();
				}
			)
		);
	}

	/**
	 * Transform shipping address to contain pickup point address?
	 *
	 * @return bool
	 */
	public function replaceShippingAddressWithPickupPointAddress(): bool {
		return (bool) $this->get( 'replace_shipping_address_with_pickup_point_address' );
	}

	/**
	 * Tells if packet cancellation should be forced.
	 *
	 * @return bool
	 */
	public function isPacketCancellationForced(): bool {
		$value = $this->get( 'force_packet_cancel' );
		if ( null !== $value ) {
			return (bool) $value;
		}

		return self::FORCE_PACKET_CANCEL_DEFAULT;
	}

	/**
	 * Gets packet auto submission payment method and event mapping.
	 *
	 * @return array
	 */
	private function getPacketAutoSubmissionPaymentMethodEventsMapping(): array {
		return $this->autoSubmissionData['payment_method_events'] ?? [];
	}

	/**
	 * Gets array of mapped events.
	 *
	 * @return string[]
	 */
	public function getPacketAutoSubmissionMappedUniqueEvents(): array {
		$mapping = $this->getPacketAutoSubmissionPaymentMethodEventsMapping();
		$result  = [];

		foreach ( $mapping as $gatewayMapping ) {
			$result[ $gatewayMapping['event'] ] = $gatewayMapping['event'];
		}

		return $result;
	}

	/**
	 * Gets packet auto-submission event by payment gateway ID.
	 *
	 * @param string $paymentGatewayId Payment gateway ID.
	 *
	 * @return string|null
	 */
	public function getPacketAutoSubmissionEvenForPaymentGateway( string $paymentGatewayId ): ?string {
		return $this->getPacketAutoSubmissionPaymentMethodEventsMapping()[ $paymentGatewayId ]['event'] ?? null;
	}

	/**
	 * Tells if packet auto submission is enabled.
	 *
	 * @return bool
	 */
	public function isPacketAutoSubmissionEnabled(): bool {
		$value = $this->autoSubmissionData['allow'] ?? null;
		if ( null !== $value ) {
			return (bool) $value;
		}

		return self::PACKET_AUTO_SUBMISSION_ALLOWED_DEFAULT;
	}

	/**
	 * Provides available labels.
	 *
	 * @return array[]
	 */
	public function getLabelFormats(): array {
		return [
			'A6 on A4'       => [
				'name'         => __( '1/4 A4, print on A4, 4pcs/page', 'packeta' ),
				'directLabels' => true,
				'maxOffset'    => 3,
			],
			'A6 on A6'       => [
				'name'         => __( '1/4 A4, direct print, 1pc/page', 'packeta' ),
				'directLabels' => true,
				'maxOffset'    => 0,
			],
			'A7 on A7'       => [
				'name'         => __( '1/8 A4, direct print, 1pc/page', 'packeta' ),
				'directLabels' => false,
				'maxOffset'    => 0,
			],
			'A7 on A4'       => [
				'name'         => __( '1/8 A4, print on A4, 8pcs/page', 'packeta' ),
				'directLabels' => false,
				'maxOffset'    => 7,
			],
			'105x35mm on A4' => [
				'name'         => __( '105x35mm, print on A4, 16 pcs/page', 'packeta' ),
				'directLabels' => false,
				'maxOffset'    => 15,
			],
			'A8 on A8'       => [
				'name'         => __( '1/16 A4, direct print, 1pc/page', 'packeta' ),
				'directLabels' => false,
				'maxOffset'    => 0,
			],
		];
	}

	/**
	 * Gets maximum offset for selected packeta labels format.
	 *
	 * @param string $format Selected format.
	 *
	 * @return int
	 */
	public function getLabelMaxOffset( string $format ): int {
		if ( '' === $format ) {
			return 0;
		}
		$availableFormats = $this->getLabelFormats();

		return $availableFormats[ $format ]['maxOffset'];
	}

	/**
	 * Gets list of packeta labels for select creation.
	 *
	 * @return array
	 */
	public function getPacketaLabelFormats(): array {
		$availableFormats = $this->getLabelFormats();

		return array_filter( array_combine( array_keys( $availableFormats ), array_column( $availableFormats, 'name' ) ) );
	}

	/**
	 * Gets list of carrier labels for select creation.
	 *
	 * @return array
	 */
	public function getCarrierLabelFormat(): array {
		$availableFormats    = $this->getLabelFormats();
		$carrierLabelFormats = [];
		foreach ( $availableFormats as $format => $formatData ) {
			if ( true === $formatData['directLabels'] ) {
				$carrierLabelFormats[ $format ] = $formatData['name'];
			}
		}

		return $carrierLabelFormats;
	}

	/**
	 * Tells if widget should open automatically.
	 *
	 * @return bool
	 */
	public function shouldWidgetOpenAutomatically(): bool {
		$value = $this->get( 'widget_auto_open' );
		if ( null !== $value ) {
			return (bool) $value;
		}

		return self::WIDGET_AUTO_OPEN_DEFAULT;
	}

	/**
	 * Force order status change on packet submit enabled.
	 *
	 * @return bool
	 */
	public function isOrderStatusAutoChangeEnabled(): bool {
		return $this->get( 'order_status_auto_change' ) ?? self::ORDER_STATUS_AUTO_CHANGE_DEFAULT;
	}

	/**
	 * Force order status change on packet submit enabled.
	 *
	 * @return bool
	 */
	public function isOrderStatusAutoChangeForAutoSubmitEnabled(): bool {
		return $this->get( 'order_status_auto_change_for_auto_submit' ) ?? self::ORDER_STATUS_AUTO_CHANGE_FOR_AUTO_SUBMIT_DEFAULT;
	}

	/**
	 * Forced order status.
	 *
	 * @return string
	 */
	public function getAutoOrderStatus(): string {
		return $this->get( 'auto_order_status' ) ?? self::AUTO_ORDER_STATUS_DEFAULT;
	}
}
