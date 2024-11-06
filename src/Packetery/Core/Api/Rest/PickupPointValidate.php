<?php
/**
 * Class PickupPointValidate
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Rest;

use Packetery\Core\Interfaces\IWebRequestClient;

/**
 * Class PickupPointValidate
 *
 * @package Packetery
 */
class PickupPointValidate {

	private const URL_VALIDATE_ENDPOINT = 'https://widget.packeta.com/v6/api/pps/api/widget/validate';

	/**
	 * HTTP client.
	 *
	 * @var IWebRequestClient
	 */
	private $webRequestClient;

	/**
	 * API key.
	 *
	 * @var string
	 */
	private $apiKey;

	/**
	 * PickupPointValidate constructor.
	 *
	 * @param IWebRequestClient $webRequestClient HTTP Client.
	 * @param string            $apiKey           API key.
	 */
	public function __construct( IWebRequestClient $webRequestClient, string $apiKey ) {
		$this->webRequestClient = $webRequestClient;
		$this->apiKey           = $apiKey;
	}

	/**
	 * Validates pickup point.
	 *
	 * @param PickupPointValidateRequest $request Pickup point validate request.
	 *
	 * @return PickupPointValidateResponse
	 * @throws RestException Thrown on failure.
	 */
	public function validate( PickupPointValidateRequest $request ): PickupPointValidateResponse {
		$postData           = $request->getSubmittableData();
		$postData['apiKey'] = $this->apiKey;
		$options            = [
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			'body'    => json_encode( $postData ),
			'headers' => [
				'Content-Type' => 'application/json',
			],
		];

		try {
			$result      = $this->webRequestClient->post( self::URL_VALIDATE_ENDPOINT, $options );
			$resultArray = json_decode( $result, true );

			return new PickupPointValidateResponse( $resultArray['isValid'], $resultArray['errors'] );
		} catch ( \Exception $exception ) {
			throw new RestException( $exception->getMessage() );
		}
	}

}
