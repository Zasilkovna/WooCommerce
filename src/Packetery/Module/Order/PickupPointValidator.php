<?php
/**
 * Class PickupPointValidator
 *
 * @package Packetery\Module
 */

declare( strict_types=1 );

namespace Packetery\Module\Order;

use Packetery\Core\Api\Rest\IDownloader;
use Packetery\Core\Api\Rest\PickupPointValidate;
use Packetery\Core\Api\Rest\PickupPointValidateRequest;
use Packetery\Core\Api\Rest\PickupPointValidateResponse;
use Packetery\Core\Api\Rest\RestException;
use Packetery\Core\Log\ILogger;
use Packetery\Core\Log\Record;
use Packetery\Module\Options\Provider;
use Packetery\GuzzleHttp\Client;
use Packetery\GuzzleHttp\Exception\GuzzleException;
use Packetery\GuzzleHttp\Psr7\Response;

/**
 * Class PickupPointValidator
 *
 * @package Packetery\Module
 */
class PickupPointValidator implements IDownloader {

	// TODO: It needs to be thoroughly tested.
	public const IS_ACTIVE = false;

	public const VALIDATION_HTTP_ERROR_SESSION_KEY = 'packetery_validation_http_error';

	/**
	 * Guzzle client.
	 *
	 * @var Client
	 */
	private $guzzleClient;

	/**
	 * Options provider.
	 *
	 * @var Provider
	 */
	private $optionsProvider;

	/**
	 * Logger.
	 *
	 * @var ILogger
	 */
	private $logger;

	/**
	 * PickupPointValidator constructor.
	 *
	 * @param Client   $guzzleClient Guzzle client.
	 * @param Provider $optionsProvider Options provider.
	 * @param ILogger  $logger Logger.
	 */
	public function __construct( Client $guzzleClient, Provider $optionsProvider, ILogger $logger ) {
		$this->guzzleClient    = $guzzleClient;
		$this->optionsProvider = $optionsProvider;
		$this->logger          = $logger;
	}

	/**
	 * Validates pickup point.
	 *
	 * @param PickupPointValidateRequest $request Pickup point validate request.
	 *
	 * @return PickupPointValidateResponse
	 */
	public function validate( PickupPointValidateRequest $request ): PickupPointValidateResponse {
		$pickupPointValidate = new PickupPointValidate( $this, $this->optionsProvider->get_api_key() );

		try {
			// We do not log successful requests.
			return $pickupPointValidate->validate( $request );
		} catch ( RestException $exception ) {
			$record         = new Record();
			$record->action = Record::ACTION_PICKUP_POINT_VALIDATE;
			$record->status = Record::STATUS_ERROR;
			$record->title  = __( 'Pickup point could not be validated.', 'packeta' );
			$record->params = [
				'errorMessage' => $exception->getMessage(),
				'request'      => $request->getSubmittableData(),
			];
			$this->logger->add( $record );
			WC()->session->set( self::VALIDATION_HTTP_ERROR_SESSION_KEY, $exception->getMessage() );

			return new PickupPointValidateResponse( true, [] );
		}
	}

	/**
	 * Accepts parameters in Guzzle format.
	 *
	 * @param string $uri Target URI.
	 * @param array  $options Options.
	 *
	 * @return string
	 * @throws GuzzleException Thrown on failure.
	 */
	public function post( string $uri, array $options ): string {
		/**
		 * Guzzle response.
		 *
		 * @var Response $result Guzzle response.
		 */
		$resultResponse = $this->guzzleClient->post( $uri, $options );

		return $resultResponse->getBody()->getContents();
	}

	/**
	 * Returns translated validation errors.
	 *
	 * @return array
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
