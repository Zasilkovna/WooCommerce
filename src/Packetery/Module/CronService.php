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
	private const CRON_CARRIERS_HOOK           = 'packetery_cron_carriers_hook';
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
	 * Constructor.
	 *
	 * @param Log\Purger         $logPurger         Log purger.
	 * @param Carrier\Downloader $carrierDownloader Carrier downloader.
	 */
	public function __construct( Log\Purger $logPurger, Carrier\Downloader $carrierDownloader ) {
		$this->logPurger         = $logPurger;
		$this->carrierDownloader = $carrierDownloader;
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

		add_action( self::CRON_CARRIERS_HOOK, [ $this->carrierDownloader, 'runAndRender' ] );
		if ( ! wp_next_scheduled( self::CRON_CARRIERS_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_CARRIERS_HOOK );
		}

		// TODO: Packet status sync.
		wp_clear_scheduled_hook( self::CRON_PACKET_STATUS_SYNC_HOOK );
	}

	/**
	 * Unregisters purger.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( self::CRON_LOG_AUTO_DELETION_HOOK );
	}
}
