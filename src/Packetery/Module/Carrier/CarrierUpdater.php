<?php

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use DateTimeImmutable;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionNames;
use Packetery\Module\Transients;
use Packetery\Nette\Http\Request;

class CarrierUpdater {

	/**
	 * @var Request
	 */
	private $httpRequest;

	/**
	 * @var Downloader
	 */
	private $downloader;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	public function __construct(
		Request $httpRequest,
		Downloader $downloader,
		WpAdapter $wpAdapter
	) {

		$this->httpRequest = $httpRequest;
		$this->downloader  = $downloader;
		$this->wpAdapter   = $wpAdapter;
	}

	public function startUpdate( string $redirectUrl ): void {
		if ( $this->httpRequest->getQuery( 'update_carriers' ) !== null ) {
			$this->wpAdapter->setTransient( Transients::RUN_UPDATE_CARRIERS, true );
			if ( $this->wpAdapter->safeRedirect( $redirectUrl ) ) {
				exit;
			}
		}
	}

	public function runUpdate(): array {
		$carriersUpdateParams = [];

		if ( $this->wpAdapter->getTransient( Transients::RUN_UPDATE_CARRIERS ) !== false ) {
			[ $carrierUpdaterResult, $carrierUpdaterClass ] = $this->downloader->run();
			$carriersUpdateParams                           = [
				'result'      => $carrierUpdaterResult,
				'resultClass' => $carrierUpdaterClass,
			];
			$this->wpAdapter->deleteTransient( Transients::RUN_UPDATE_CARRIERS );
		}

		return $carriersUpdateParams;
	}

	public function getLastUpdate(): ?string {
		$lastCarrierUpdate = $this->wpAdapter->getOption( OptionNames::LAST_CARRIER_UPDATE );
		if ( $lastCarrierUpdate !== false ) {
			$date = DateTimeImmutable::createFromFormat( DATE_ATOM, $lastCarrierUpdate );
			if ( $date !== false ) {
				$date->setTimezone( $this->wpAdapter->timezone() );

				return $date->format( $this->wpAdapter->getOption( 'date_format' ) . ' ' . $this->wpAdapter->getOption( 'time_format' ) );
			}
		}

		return null;
	}
}
