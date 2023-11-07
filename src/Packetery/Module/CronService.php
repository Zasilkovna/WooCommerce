<?php
/**
 * Class CronService
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module;

/**
 * Class CronService
 *
 * @package Packetery
 */
class CronService {

	public const CRON_LOG_AUTO_DELETION_HOOK           = 'packetery_cron_log_auto_deletion_hook';
	public const CRON_CARRIERS_HOOK                    = 'packetery_cron_carriers_hook';
	public const CRON_PACKET_STATUS_SYNC_HOOK          = 'packetery_cron_packet_status_sync_hook';
	private const CRON_PACKET_STATUS_SYNC_HOOK_WEEKEND = 'packetery_cron_packet_status_sync_hook_weekend';

	/**
	 * Log purger.
	 *
	 * @var Log\Purger
	 */
	private $logPurger;

	/**
	 * Carrier downloader.
	 *
	 * @var Carrier\Downloader
	 */
	private $carrierDownloader;

	/**
	 * Packet synchronizer.
	 *
	 * @var Order\PacketSynchronizer
	 */
	private $packetSynchronizer;

	/**
	 * Constructor.
	 *
	 * @param Log\Purger               $logPurger Log purger.
	 * @param Carrier\Downloader       $carrierDownloader Carrier downloader.
	 * @param Order\PacketSynchronizer $packetSynchronizer Packet synchronizer.
	 */
	public function __construct( Log\Purger $logPurger, Carrier\Downloader $carrierDownloader, Order\PacketSynchronizer $packetSynchronizer ) {
		$this->logPurger          = $logPurger;
		$this->carrierDownloader  = $carrierDownloader;
		$this->packetSynchronizer = $packetSynchronizer;
	}

	/**
	 * Registers service.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		add_action(
			'init',
			function () {
				add_action( self::CRON_LOG_AUTO_DELETION_HOOK, [ $this->logPurger, 'autoDeleteHook' ] );
				if ( false === as_has_scheduled_action( self::CRON_LOG_AUTO_DELETION_HOOK ) ) {
					as_schedule_recurring_action( ( new \DateTime( 'next day 02:00', wp_timezone() ) )->getTimestamp(), DAY_IN_SECONDS, self::CRON_LOG_AUTO_DELETION_HOOK );
				}
			}
		);

		add_action(
			'init',
			function () {
				add_action( self::CRON_CARRIERS_HOOK, [ $this->carrierDownloader, 'runAndRender' ] );
				if ( false === as_has_scheduled_action( self::CRON_CARRIERS_HOOK ) ) {
					as_schedule_recurring_action( ( new \DateTime( 'next day 09:10', wp_timezone() ) )->getTimestamp(), DAY_IN_SECONDS, self::CRON_CARRIERS_HOOK );
				}
			}
		);

		add_action(
			'init',
			function () {
				add_action( self::CRON_PACKET_STATUS_SYNC_HOOK, [ $this->packetSynchronizer, 'syncStatuses' ] );
				if ( false === as_has_scheduled_action( self::CRON_PACKET_STATUS_SYNC_HOOK ) ) {
					// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
					// Monday to Friday at 02:10, 06:10, 10:10, 14:10, 18:10, 22:10.
					as_schedule_cron_action( ( new \DateTime() )->getTimestamp(), '10 2,6,10,14,18,22 * * 1-5', self::CRON_PACKET_STATUS_SYNC_HOOK );
				}
			}
		);

		add_action(
			'init',
			function () {
				add_action( self::CRON_PACKET_STATUS_SYNC_HOOK_WEEKEND, [ $this->packetSynchronizer, 'syncStatuses' ] );
				if ( false === as_has_scheduled_action( self::CRON_PACKET_STATUS_SYNC_HOOK_WEEKEND ) ) {
					// Saturday, Sunday at 03:10.
					as_schedule_cron_action( ( new \DateTime() )->getTimestamp(), '10 3 * * 6,0', self::CRON_PACKET_STATUS_SYNC_HOOK_WEEKEND );
				}
			}
		);
	}

	/**
	 * Unregisters purger and synchronizer.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		as_unschedule_action( self::CRON_LOG_AUTO_DELETION_HOOK );
		as_unschedule_action( self::CRON_CARRIERS_HOOK );
		as_unschedule_action( self::CRON_PACKET_STATUS_SYNC_HOOK );
		as_unschedule_action( self::CRON_PACKET_STATUS_SYNC_HOOK_WEEKEND );
	}
}
