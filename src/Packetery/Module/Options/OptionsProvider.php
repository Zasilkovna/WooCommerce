<?php
/**
 * Class OptionsProvider
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

use Packetery\Core\Entity\PacketStatus;
use Packetery\Module\Order\PacketSynchronizer;

/**
 * Class OptionsProvider
 *
 * @package Packetery
 */
class OptionsProvider {

	public const OPTION_NAME_PACKETERY                 = 'packetery';
	public const OPTION_NAME_PACKETERY_SYNC            = 'packetery_sync';
	public const OPTION_NAME_PACKETERY_AUTO_SUBMISSION = 'packetery_auto_submission';

	public const DEFAULT_VALUE_PACKETA_LABEL_FORMAT        = 'A6 on A4';
	public const DEFAULT_VALUE_CARRIER_LABEL_FORMAT        = self::DEFAULT_VALUE_PACKETA_LABEL_FORMAT;
	public const MAX_STATUS_SYNCING_PACKETS_DEFAULT        = 100;
	public const MAX_DAYS_OF_PACKET_STATUS_SYNCING_DEFAULT = 14;
	public const FORCE_PACKET_CANCEL_DEFAULT               = true;
	public const PACKET_AUTO_SUBMISSION_ALLOWED_DEFAULT    = false;
	public const WIDGET_AUTO_OPEN_DEFAULT                  = false;
	public const AUTO_ORDER_STATUS_DEFAULT                 = '';
	public const EMAIL_HOOK_DEFAULT                        = 'woocommerce_email_footer';
	public const AUTO_ORDER_STATUS                         = 'auto_order_status';
	public const DISPLAY_FREE_SHIPPING_IN_CHECKOUT_DEFAULT = true;
	public const PRICES_INCLUDE_TAX_DEFAULT                = false;

	public const AUTOMATIC_CHECKOUT_DETECTION = 'automatic_checkout_detection';
	public const BLOCK_CHECKOUT_DETECTION     = 'block_checkout_detection';
	public const CLASSIC_CHECKOUT_DETECTION   = 'classic_checkout_detection';

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
	 * OptionsProvider constructor.
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
	 * Gets data section.
	 *
	 * @param string $optionsName Option name of settings.
	 *
	 * @return array<string, mixed> Only section of options data by given $optionName.
	 * @throws \InvalidArgumentException When provided option name does not exist.
	 */
	public function getOptionsByName( string $optionsName ): array {
		$data = $this->getAllOptions();
		if ( ! isset( $data[ $optionsName ] ) ) {
			throw new \InvalidArgumentException( sprintf( 'Option name "%s" does not exist.', $optionsName ) );
		}

		return $data[ $optionsName ];
	}

	/**
	 * Gets data as array.
	 *
	 * @return array<string, array> All plugin options data by given $optionName.
	 */
	public function getAllOptions(): array {
		return [
			self::OPTION_NAME_PACKETERY                 => $this->data,
			self::OPTION_NAME_PACKETERY_SYNC            => $this->syncData,
			self::OPTION_NAME_PACKETERY_AUTO_SUBMISSION => $this->autoSubmissionData,
		];
	}

	/**
	 * Tells if provider has any data,
	 *
	 * @param string $optionName Option name of settings.
	 *
	 * @return bool Has any data.
	 */
	public function has_any( string $optionName ): bool {
		return ! empty( $this->getOptionsByName( $optionName ) );
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
	 * Returns COD payment methods.
	 *
	 * @return string[] Values.
	 */
	public function getCodPaymentMethods(): array {
		$value = $this->get( 'cod_payment_methods' );
		if ( ! $value ) {
			return [];
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
	 * Returns which checkout detection to return
	 *
	 * @return string
	 */
	public function getCheckoutDetection(): string {
		$value = $this->get( 'checkout_detection' );
		if ( ! $value ) {
			return self::AUTOMATIC_CHECKOUT_DETECTION;
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
	 * Consignment's default dimensions enabled.
	 *
	 * @return bool
	 */
	public function isDefaultDimensionsEnabled(): bool {
		return (bool) $this->get( 'default_dimensions_enabled' );
	}

	/**
	 * Consignment's default length.
	 *
	 * @return float
	 */
	public function getDefaultLength(): float {
		if ( $this->get( 'default_length' ) === null ) {
			return 0.0;
		}
		return (float) $this->get( 'default_length' );
	}

	/**
	 * Consignment's default height.
	 *
	 * @return float
	 */
	public function getDefaultHeight(): float {
		if ( $this->get( 'default_height' ) === null ) {
			return 0.0;
		}
		return (float) $this->get( 'default_height' );
	}

	/**
	 * Consignment's default width.
	 *
	 * @return float
	 */
	public function getDefaultWidth(): float {
		if ( $this->get( 'default_width' ) === null ) {
			return 0.0;
		}
		return (float) $this->get( 'default_width' );
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
	 * Turns on/off free shipping text in checkout.
	 *
	 * @return bool
	 */
	public function isFreeShippingShown(): bool {
		$freeShippingStatus = $this->get( 'free_shipping_shown' );
		if ( null !== $freeShippingStatus ) {
			return (bool) $freeShippingStatus;
		}

		return self::DISPLAY_FREE_SHIPPING_IN_CHECKOUT_DEFAULT;
	}

	/**
	 * Tells if prices include tax.
	 *
	 * @return bool
	 */
	public function arePricesTaxInclusive(): bool {
		$pricesIncludeTax = $this->get( 'prices_include_tax' );
		if ( null !== $pricesIncludeTax ) {
			return (bool) $pricesIncludeTax;
		}

		return self::PRICES_INCLUDE_TAX_DEFAULT;
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
	public function getPacketAutoSubmissionEventForPaymentGateway( string $paymentGatewayId ): ?string {
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
	 * Gets maximum offset for selected Packeta labels format.
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
	 * Gets list of Packeta labels for select creation.
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
	 * Auto order status change on packet submit enabled. Used in upgrade only.
	 *
	 * @return bool
	 */
	public function isOrderStatusAutoChangeEnabled(): bool {
		$orderStatusAutoChange = $this->get( 'order_status_auto_change' );
		if ( null !== $orderStatusAutoChange ) {
			return (bool) $orderStatusAutoChange;
		}

		return false;
	}

	/**
	 * Auto order status change.
	 *
	 * @return bool
	 */
	public function isOrderStatusChangeAllowed(): bool {
		$allowOrderStatusChange = ( $this->syncData['allow_order_status_change'] ?? null );
		if ( null !== $allowOrderStatusChange ) {
			return (bool) $allowOrderStatusChange;
		}

		return false;
	}

	/**
	 * Tells auto order status, if it is valid, otherwise empty string.
	 *
	 * @param string $packetStatus Packet status.
	 *
	 * @return string
	 */
	public function getValidAutoOrderStatusFromMapping( string $packetStatus ): string {
		$autoOrderStatus = $this->getAutoOrderStatusFromMapping( $packetStatus );
		if ( wc_is_order_status( $autoOrderStatus ) ) {
			return $autoOrderStatus;
		}

		return self::AUTO_ORDER_STATUS_DEFAULT;
	}

	/**
	 * Tells auto order status.
	 *
	 * @param string $packetStatus Packet status.
	 *
	 * @return string|null
	 */
	public function getAutoOrderStatusFromMapping( string $packetStatus ): ?string {
		return $this->syncData['order_status_change_packet_statuses'][ $packetStatus ] ?? null;
	}

	/**
	 * Tells auto order status. Used in upgrade only.
	 *
	 * @return string|null
	 */
	public function getAutoOrderStatus(): ?string {
		return $this->get( self::AUTO_ORDER_STATUS );
	}

	/**
	 * Gets email hook.
	 *
	 * @since 1.6.1
	 * @return string
	 */
	public function getEmailHook(): string {
		$emailHook = $this->get( 'email_hook' );

		return ( null !== $emailHook ? $emailHook : self::EMAIL_HOOK_DEFAULT );
	}

	/**
	 * Performs replacements needed by Nette form to pass validation, see https://github.com/dg/nette-component-model/blob/master/src/ComponentModel/Container.php#L50 .
	 * There may be an edge case where a replacement causes a conflict with another method. We do not address this issue yet.
	 *
	 * @param string $id Payment gateway id.
	 *
	 * @return string
	 */
	public function sanitizePaymentGatewayId( string $id ): string {
		return preg_replace( '/\W/', '_', $id );
	}

}
