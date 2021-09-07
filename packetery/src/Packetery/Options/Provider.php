<?php
/**
 * Class Provider
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Options;

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
	 * Which payment rate id COD?
	 *
	 * @return string Content.
	 */
	public function getCodPaymentMethod(): string {
		return (string) $this->get( 'cod_payment_method' );
	}

	/**
	 * Provides available labels.
	 *
	 * @return array[]
	 */
	public function getLabelFormats() {
		return [
			'A6 on A4'       => [
				'name'         => __( '105x148 mm (A6) label on a page of size 210x297 mm (A4)', 'packetery' ),
				'directLabels' => true,
				'maxOffset'    => 3,
			],
			'A6 on A6'       => [
				'name'         => __( '105x148 mm (A6) label on a page of the same size', 'packetery' ),
				'directLabels' => true,
				'maxOffset'    => 0,
			],
			'A7 on A7'       => [
				'name'         => __( '105x74 mm (A7) label on a page of the same size', 'packetery' ),
				'directLabels' => false,
				'maxOffset'    => 0,
			],
			'A7 on A4'       => [
				'name'         => __( '105x74 mm (A7) label on a page of size 210x297 mm (A4)', 'packetery' ),
				'directLabels' => false,
				'maxOffset'    => 7,
			],
			'105x35mm on A4' => [
				'name'         => __( '105x35 mm label on a page of size 210x297 mm (A4)', 'packetery' ),
				'directLabels' => false,
				'maxOffset'    => 15,
			],
			'A8 on A8'       => [
				'name'         => __( '50x74 mm (A8) label on a page of the same size', 'packetery' ),
				'directLabels' => false,
				'maxOffset'    => 0,
			],
		];
	}
}
