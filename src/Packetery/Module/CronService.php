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

	private const CRON_LOG_AUTO_DELETION_HOOK  = 'packetery_cron_log_auto_deletion_hook';
	public const CRON_CARRIERS_HOOK            = 'packetery_cron_carriers_hook';
	private const CRON_PACKET_STATUS_SYNC_HOOK = 'packetery_cron_packet_status_sync_hook';

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
		add_action( self::CRON_LOG_AUTO_DELETION_HOOK, [ $this->logPurger, 'autoDeleteHook' ] );
		if ( ! wp_next_scheduled( self::CRON_LOG_AUTO_DELETION_HOOK ) ) {
			wp_schedule_event( ( new \DateTime( 'next day 02:00', wp_timezone() ) )->getTimestamp(), 'daily', self::CRON_LOG_AUTO_DELETION_HOOK );
		}

		add_action(
			'init',
			function () {
				add_action( self::CRON_CARRIERS_HOOK, [ $this->carrierDownloader, 'runAndRender' ] );
				if ( false === as_has_scheduled_action( self::CRON_CARRIERS_HOOK ) ) {
					as_schedule_recurring_action( ( new \DateTime( 'next day 09:10', wp_timezone() ) )->getTimestamp(), 86400, self::CRON_CARRIERS_HOOK );
				}
			}
		);

		add_action( self::CRON_PACKET_STATUS_SYNC_HOOK, [ $this->packetSynchronizer, 'syncStatuses' ] );
		if ( ! wp_next_scheduled( self::CRON_PACKET_STATUS_SYNC_HOOK ) ) {
			wp_schedule_event( ( new \DateTime( 'next day 03:00:00', wp_timezone() ) )->getTimestamp(), 'daily', self::CRON_PACKET_STATUS_SYNC_HOOK );
		}
	}

	/**
	 * Unregisters purger and synchronizer.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( self::CRON_LOG_AUTO_DELETION_HOOK );
		as_unschedule_action( self::CRON_CARRIERS_HOOK );
		wp_clear_scheduled_hook( self::CRON_PACKET_STATUS_SYNC_HOOK );
	}
}
