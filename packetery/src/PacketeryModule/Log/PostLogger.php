<?php

declare( strict_types=1 );


namespace PacketeryModule\Log;

use Packetery\Log\ILogger;
use Packetery\Log\Record;

class PostLogger implements ILogger {

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
	public function register(): void {
		$definition = [
			'labels'          => [ 'name' => __( 'Logs', 'packetery' ) ],
			'public'          => false,
			'query_var'       => false,
			'rewrite'         => false,
			'capability_type' => 'post',
			'supports'        => [ 'title', 'editor' ],
			'can_export'      => false,
		];

		register_post_type( self::POST_TYPE, $definition );
	}

	/**
	 * Create new log entry
	 *
	 * This is just a simple and fast way to log something. Use self::insert_log()
	 * if you need to store custom meta data
	 *
	 * @param Record $record Record.
	 *
	 * @return void
	 */
	public function add( Record $record ): void {
		$logData = [
			'post_title'   => $record->title ?? '',
			'post_content' => serialize( ( $record->params ?? [] ) ),
			'post_type'    => self::POST_TYPE,
			'post_status'  => 'publish',
			'post_parent'  => 0,
			'log_type'     => false,
		];

		$metaData = [
			'packetery_status' => ( $record->status ?? '' ),
			'packetery_action' => ( $record->action ?? '' ),
		];

		$logId = wp_insert_post( $logData );

		if ( $logId ) {
			foreach ( $metaData as $key => $meta ) {
				update_post_meta( $logId, $key, $meta );
			}
		}
	}

	/**
	 * Retrieve all connected logs. Used for retrieving logs related to particular items, such as a specific purchase.
	 *
	 * @param array $sorting
	 *
	 * @return Record[]
	 */
	public function getRecords( array $sorting = [] ): array {
		$defaults = [
			'post_parent' => 0,
			'post_type'   => self::POST_TYPE,
			'post_status' => 'publish',
			'log_type'    => false,
		];

		$arguments = [
			'orderby' => $sorting,
		];

		$queryArgs = wp_parse_args( $arguments, $defaults );
		$logs      = get_posts( $queryArgs );

		if ( ! $logs ) {
			return [];
		}

		return array_map( static function ( \WP_Post $log ) {
			$record         = new Record();
			$record->status = get_post_meta( $log->ID, 'packetery_status', true );
			$record->date   = \DateTimeImmutable::createFromMutable( wc_string_to_datetime( $log->post_date ) );
			$record->action = get_post_meta( $log->ID, 'packetery_action', true );
			$record->title  = $log->post_title;
			$record->params = unserialize( $log->post_content, [ 'allowed_classes' => true ] );

			return $record;
		}, $logs );
	}
}
