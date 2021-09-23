<?php
/**
 * Class Entity
 *
 * @package Packetery\Log
 */

declare( strict_types=1 );


namespace Packetery\Log;

/**
 * Class Entity
 *
 * @package Packetery\Log
 */
class Entity {

	/**
	 * Post.
	 *
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * Entity constructor.
	 *
	 * @param \WP_Post $post Post.
	 */
	public function __construct( \WP_Post $post ) {
		$this->post = $post;
	}

	/**
	 * Gets packetery status.
	 *
	 * @return string
	 */
	public function getStatus(): string {
		return $this->getMeta( 'packetery_status' );
	}

	/**
	 * Gets date of post creation.
	 *
	 * @return string
	 */
	public function getDate(): string {
		return $this->post->post_date;
	}

	/**
	 * Gets date of post creation formatted by global user defined format.
	 *
	 * @return string
	 */
	public function getDateFormatted(): string {
		return wc_format_datetime( wc_string_to_datetime( $this->getDate() ), wc_date_format() . ' ' . wc_time_format() );
	}

	/**
	 * Gets packetery action defined in ActionEnum.
	 *
	 * @return string
	 */
	public function getAction(): string {
		return $this->getMeta( 'packetery_action' );
	}

	/**
	 * Translates action.
	 *
	 * @return string|null
	 */
	public function getTranslatedAction(): ?string {
		$action = $this->getAction();

		switch ( $action ) {
			case ActionEnum::PACKET_SENDING:
				return __( 'logAction_packet-sending', 'packetery' );
			case ActionEnum::CARRIER_LABEL_PRINT:
				return __( 'logAction_carrier-label-print', 'packetery' );
			case ActionEnum::LABEL_PRINT:
				return __( 'logAction_label-print', 'packetery' );
			case ActionEnum::CARRIER_LIST_UPDATE:
				return __( 'logAction_carrier-list-update', 'packetery' );
		}

		return null;
	}

	/**
	 * Note.
	 *
	 * @return string
	 */
	public function getNote(): string {
		return $this->post->post_content;
	}

	/**
	 * Gets post metadata.
	 *
	 * @param string $key Metadata key.
	 *
	 * @return string|null
	 */
	private function getMeta( string $key ): ?string {
		$value = get_post_meta( $this->post->ID, $key, true );
		if ( ! $value ) {
			return null;
		}

		return $value;
	}
}
