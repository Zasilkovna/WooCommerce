<?php
/**
 * Class PostLogger
 *
 * @package Packetery\Module\Log
 */

declare( strict_types=1 );


namespace Packetery\Module\Log;

use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;

/**
 * Class PostLogger
 *
 * @package Packetery\Module\Log
 */
class PostLogger implements ILogger {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	public const POST_TYPE = 'packetery_log';

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
			'post_content' => ( ! empty( $record->params ) ? wp_json_encode( $record->params, ILogger::JSON_FLAGS ) : '' ),
			'post_type'    => self::POST_TYPE,
			'post_status'  => 'publish',
			'post_parent'  => 0,
			'log_type'     => false,
		];

		$logData['post_content'] = str_replace('\\', '&quot;', $logData['post_content']);

		$metaData = [
			'packetery_status'    => ( $record->status ?? '' ),
			'packetery_action'    => ( $record->action ?? '' ),
			'packetery_custom_id' => ( $record->customId ?? '' ),
		];

		if ( $record->customId ) {
			$oldPostIds = get_posts(
				[
					'post_type'      => self::POST_TYPE,
					'post_status'    => 'any',
					'nopaging'       => true,
					'posts_per_page' => - 1,
					'fields'         => 'ids',
					'meta_query'     => [
						[
							'key'   => 'packetery_custom_id',
							'value' => $record->customId,
						],
					],
				]
			);

			foreach ( $oldPostIds as $old_post_id ) {
				wp_delete_post( $old_post_id ); // There can be only one record with such custom id. We always want newest one.
			}
		}

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
	 * @param array $sorting Sorting.
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
			'orderby'     => $sorting,
			'numberposts' => 100,
		];

		$queryArgs = wp_parse_args( $arguments, $defaults );
		$logs      = get_posts( $queryArgs );

		if ( ! $logs ) {
			return [];
		}

		return array_map(
			static function ( \WP_Post $log ) {
				$record         = new Record();
				$record->status = get_post_meta( $log->ID, 'packetery_status', true );
				$record->date   = \DateTimeImmutable::createFromMutable( wc_string_to_datetime( $log->post_date ) );
				$record->action = get_post_meta( $log->ID, 'packetery_action', true );
				$record->title  = $log->post_title;
				$postContent    = str_replace( '&quot;', '\\', $log->post_content );
				$record->params = @json_decode( $postContent, true, 512, ILogger::JSON_FLAGS );

				return $record;
			},
			$logs
		);
	}
}
