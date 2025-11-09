<?php

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Rest\Exception\InvalidApiKeyException;
use Packetery\Core\Api\Rest\PickupPointValidate;
use Packetery\Core\Api\Rest\PickupPointValidateRequest;
use Packetery\Core\Api\Rest\PickupPointValidateResponse;
use Packetery\Core\Api\Rest\RestException;
use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\WebRequestClient;

class PickupPointValidator {

	public const VALIDATION_HTTP_ERROR_SESSION_KEY = 'packetery_validation_http_error';

	/** @var OptionsProvider */
	private $optionsProvider;

	/** @var ILogger */
	private $logger;

	/** @var WebRequestClient */
	private $webRequestClient;

	/** @var WpAdapter */
	private $wpAdapter;

	/** @var WcAdapter */
	private $wcAdapter;

	public function __construct(
		OptionsProvider $optionsProvider,
		ILogger $logger,
		WebRequestClient $webRequestClient,
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter
	) {
		$this->optionsProvider  = $optionsProvider;
		$this->logger           = $logger;
		$this->webRequestClient = $webRequestClient;
		$this->wpAdapter        = $wpAdapter;
		$this->wcAdapter        = $wcAdapter;
	}

	public function validate( PickupPointValidateRequest $request ): PickupPointValidateResponse {
		$apiKey = $this->optionsProvider->get_api_key();
		try {
			$pickupPointValidate = PickupPointValidate::createWithValidApiKey( $this->webRequestClient, $apiKey );
		} catch ( InvalidApiKeyException $exception ) {
			$record         = $this->createPickUpPointValidateErrorRecord();
			$record->params = [
				'errorMessage' => $this->wpAdapter->__( 'API credentials are not set correctly.', 'packeta' ),
			];

			$this->logger->add( $record );

			return new PickupPointValidateResponse( true, [] );
		}

		try {
			$validationResponse = $pickupPointValidate->validate( $request );

			if ( $validationResponse->isValid() === false ) {
				$record = $this->createPickUpPointValidateErrorRecord();
			} else {
				$record         = new Record();
				$record->action = Record::ACTION_PICKUP_POINT_VALIDATE;
				$record->status = Record::STATUS_SUCCESS;
				$record->title  = $this->wpAdapter->__( 'Pickup point validated.', 'packeta' );
			}
			$record->params = [
				'request' => $request->getSubmittableData(),
				'isValid' => $validationResponse->isValid(),
				'errors'  => $validationResponse->getErrors(),
			];
			$this->logger->add( $record );

			return $validationResponse;
		} catch ( RestException $exception ) {
			$record         = $this->createPickUpPointValidateErrorRecord();
			$record->params = [
				'errorMessage' => $exception->getMessage(),
				'request'      => $request->getSubmittableData(),
			];

			$this->logger->add( $record );
			$this->wcAdapter->sessionSet( self::VALIDATION_HTTP_ERROR_SESSION_KEY, $exception->getMessage() );

			return new PickupPointValidateResponse( true, [] );
		}
	}

	private function createPickUpPointValidateErrorRecord(): Record {
		$record         = new Record();
		$record->action = Record::ACTION_PICKUP_POINT_VALIDATE;
		$record->status = Record::STATUS_ERROR;
		$record->title  = $this->wpAdapter->__( 'Pickup point could not be validated.', 'packeta' );

		return $record;
	}
}
