<?php
/**
 * Class for logging events and errors
 *
 * @package     WP Logging Class
 */

declare( strict_types=1 );


namespace Packetery\Log;

/**
 * Class for logging events and errors
 *
 * @package     WP Logging Class
 */
class Manager {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	private const POST_TYPE = 'packetery_log';

	/**
	 * Registers the wp_log Post Type
	 *
	 * @return void
	 */
	public function registerPostType(): void {
		$definition = array(
			'labels'          => array( 'name' => __( 'Logs', 'packetery' ) ),
			'public'          => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'supports'        => array( 'title', 'editor' ),
			'can_export'      => false,
		);

		register_post_type( self::POST_TYPE, $definition );
	}

	/**
	 * Create new log entry
	 *
	 * This is just a simple and fast way to log something. Use self::insert_log()
	 * if you need to store custom meta data
	 *
	 * @param string $status Status.
	 * @param string $action Action.
	 * @param string $note   Note.
	 *
	 * @return int The ID of the new log entry
	 */
	public function add( string $status, string $action, string $note ): int {
		$logData = array(
			'post_title'   => 'packetery_log',
			'post_content' => $note,
		);

		$metaData = array(
			'packetery_status' => $status,
			'packetery_action' => $action,
		);

		return $this->insertLog( $logData, $metaData );
	}

	/**
	 * Stores a log entry
	 *
	 * @param array $logData Main post data.
	 * @param array $logMeta Post metadata.
	 *
	 * @return int The ID of the newly created log item
	 */
	private function insertLog( array $logData = array(), array $logMeta = array() ): int {
		$defaults = array(
			'post_type'    => self::POST_TYPE,
			'post_status'  => 'publish',
			'post_parent'  => 0,
			'post_content' => '',
			'log_type'     => false,
		);

		$args  = wp_parse_args( $logData, $defaults );
		$logId = wp_insert_post( $args );

		if ( $logId && ! empty( $logMeta ) ) {
			foreach ( $logMeta as $key => $meta ) {
				update_post_meta( $logId, sanitize_key( $key ), $meta );
			}
		}

		return $logId;
	}

	/**
	 * Retrieve all connected logs. Used for retrieving logs related to particular items, such as a specific purchase.
	 *
	 * @param array $arguments Query arguments.
	 *
	 * @return Entity[]
	 */
	public function getLogs( array $arguments = array() ): array {
		$defaults = array(
			'post_parent' => 0,
			'post_type'   => self::POST_TYPE,
			'post_status' => 'publish',
			'log_type'    => false,
		);

		$queryArgs = wp_parse_args( $arguments, $defaults );
		$logs      = get_posts( $queryArgs );

		if ( $logs ) {
			return array_map(
				function ( \WP_Post $log ) {
					return new Entity( $log );
				},
				$logs
			);
		}

		return array();
	}
}
