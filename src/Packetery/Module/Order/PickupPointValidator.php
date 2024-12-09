<?php
/**
 * Class PickupPointValidator
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Rest\Exception\InvalidApiKeyException;
use Packetery\Core\Api\Rest\PickupPointValidate;
use Packetery\Core\Api\Rest\PickupPointValidateRequest;
use Packetery\Core\Api\Rest\PickupPointValidateResponse;
use Packetery\Core\Api\Rest\RestException;
use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\WebRequestClient;

/**
 * Class PickupPointValidator
 *
 * @package Packetery\Module
 */
class PickupPointValidator {

	// TODO: It needs to be thoroughly tested.
	public const IS_ACTIVE = false;

	public const VALIDATION_HTTP_ERROR_SESSION_KEY = 'packetery_validation_http_error';

	/**
	 * Options provider.
	 *
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	/**
	 * Logger.
	 *
	 * @var ILogger
	 */
	private $logger;

	/**
	 * HTTP Client.
	 *
	 * @var WebRequestClient
	 */
	private $webRequestClient;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @param OptionsProvider  $optionsProvider
	 * @param ILogger          $logger
	 * @param WebRequestClient $webRequestClient
	 * @param WpAdapter        $wpAdapter
	 */
	public function __construct(
		OptionsProvider $optionsProvider,
		ILogger $logger,
		WebRequestClient $webRequestClient,
		WpAdapter $wpAdapter
	) {
		$this->optionsProvider  = $optionsProvider;
		$this->logger           = $logger;
		$this->webRequestClient = $webRequestClient;
		$this->wpAdapter        = $wpAdapter;
	}

	/**
	 * Validates pickup point.
	 *
	 * @param PickupPointValidateRequest $request Pickup point validate request.
	 *
	 * @return PickupPointValidateResponse
	 */
	public function validate( PickupPointValidateRequest $request ): PickupPointValidateResponse {
		$apiKey     = $this->optionsProvider->get_api_key();
		$errorTitle = $this->wpAdapter->__( 'Pickup point could not be validated.', 'packeta' );

		try {
			$pickupPointValidate = new PickupPointValidate( $this->webRequestClient, $apiKey );
		} catch ( InvalidApiKeyException $exception ) {
			$this->logErrorRecord( $exception->getMessage(), $apiKey, $errorTitle );

			return new PickupPointValidateResponse( true, [] );
		}

		try {
			// We do not log successful requests.
			return $pickupPointValidate->validate( $request );
		} catch ( RestException $exception ) {
			$this->logErrorRecord( $exception->getMessage(), $request->getSubmittableData(), $errorTitle );
			WC()->session->set( self::VALIDATION_HTTP_ERROR_SESSION_KEY, $exception->getMessage() );

			return new PickupPointValidateResponse( true, [] );
		}
	}

	/**
	 * @param string                                            $exceptionMessage
	 * @param null|string|array<string, string|bool|float|null> $details
	 * @param string                                            $errorTitle
	 *
	 * @return void
	 */
	private function logErrorRecord( string $exceptionMessage, $details, string $errorTitle ): void {
		$record         = new Record();
		$record->action = Record::ACTION_PICKUP_POINT_VALIDATE;
		$record->status = Record::STATUS_ERROR;
		$record->title  = $errorTitle;
		$record->params = [
			'errorMessage' => $exceptionMessage,
			'details'      => $details,
		];
		$this->logger->add( $record );
	}

	/**
	 * Returns translated validation errors.
	 *
	 * @return array<string, string>
	 */
	public function getTranslatedError(): array {
		return [
			'NotFound'                    => __( 'The pick-up point was not found.', 'packeta' ),
			'InvalidCarrier'              => __( 'The pick-up point has not allowed carrier.', 'packeta' ),
			'InvalidCountry'              => __( 'The pick-up point is not in allowed country.', 'packeta' ),
			'EmptyListOfAllowedCountries' => __( 'Cannot perform country validation because the list of allowed countries is empty.', 'packeta' ),
			'NoClaimAssistant'            => __( 'The pick-up point does not offer Complaints Assistant Service.', 'packeta' ),
			'NoPacketConsignment'         => __( 'The pick-up point is not submission point.', 'packeta' ),
			'InvalidWeight'               => __( 'The pick-up point does not accept packets with given weight.', 'packeta' ),
			'NoAgeVerification'           => __( 'The pick-up point does not offer Age Verification Service.', 'packeta' ),
			'PickupPointVacation'         => __( 'The pick-up point currently does not accept any packets due to reported holiday.', 'packeta' ),
			'PickupPointClosing'          => __( 'The pick-up point does not accept new shipments because it will be closed soon.', 'packeta' ),
			'PickupPointIsFull'           => __( 'The pick-up point does not accept any packets at the moment due to its full capacity.', 'packeta' ),
			'PickupPointForbidden'        => __( 'The pick-up point cannot be selected.', 'packeta' ),
			'PickupPointTechnicalReason'  => __( 'The pick-up point cannot be chosen as a final destination of your packet due to technical reasons.', 'packeta' ),
		];
	}
}
