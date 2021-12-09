<?php
/**
 * Class Provider
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Options;

/**
 * Class Provider
 *
 * @package Packetery
 */
class Provider {

	/**
	 *  Options data.
	 *
	 * @var array
	 */
	private $data;

	/**
	 * Provider constructor.
	 */
	public function __construct() {
		$data = get_option( 'packetery' );
		if ( ! $data ) {
			$data = array();
		}

		$this->data = $data;
	}

	/**
	 * Casts data to array.
	 *
	 * @return array Data.
	 */
	public function data_to_array(): array {
		return $this->data;
	}

	/**
	 * Casts data to array.
	 *
	 * @return bool Has any data.
	 */
	public function has_any(): bool {
		return ! empty( $this->data );
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
	 * @return string|null Content.
	 */
	public function get_carrier_label_format(): ?string {
		return $this->get( 'carrier_label_format' );
	}

	/**
	 * Packeta label format.
	 *
	 * @return string|null Content.
	 */
	public function get_packeta_label_format(): ?string {
		return $this->get( 'packeta_label_format' );
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
	 * Gets number of orders per page in WC order grid. Used by add_filter().
	 *
	 * @return int|null Per page.
	 */
	public function getOrderGridPerPage(): ?int {
		$value = $this->get( 'order_grid_per_page' );
		if ( is_numeric( $value ) ) {
			return (int) $value;
		}

		return null;
	}

	/**
	 * Gets priority for add_filter() callback related to order_grid_per_page.
	 *
	 * @return int Per page priority.
	 */
	public function getOrderGridPerPagePriority(): int {
		$value = $this->get( 'order_grid_per_page_priority' );
		if ( is_numeric( $value ) ) {
			return (int) $value;
		}

		return 10;
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
	 * Provides available labels.
	 *
	 * @return array[]
	 */
	public function getLabelFormats(): array {
		return [
			'A6 on A4'       => [
				'name'         => __( 'labelNameA6onA4', 'packetery' ),
				'directLabels' => true,
				'maxOffset'    => 3,
			],
			'A6 on A6'       => [
				'name'         => __( 'labelNameA6onA6', 'packetery' ),
				'directLabels' => true,
				'maxOffset'    => 0,
			],
			'A7 on A7'       => [
				'name'         => __( 'labelNameA7onA7', 'packetery' ),
				'directLabels' => false,
				'maxOffset'    => 0,
			],
			'A7 on A4'       => [
				'name'         => __( 'labelNameA7onA4', 'packetery' ),
				'directLabels' => false,
				'maxOffset'    => 7,
			],
			'105x35mm on A4' => [
				'name'         => __( 'labelName105x35onA4', 'packetery' ),
				'directLabels' => false,
				'maxOffset'    => 15,
			],
			'A8 on A8'       => [
				'name'         => __( 'labelNameA8onA8', 'packetery' ),
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
}
