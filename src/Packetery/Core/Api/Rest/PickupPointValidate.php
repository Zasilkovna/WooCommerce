<?php

declare( strict_types=1 );

namespace Packetery\Core\Api\Rest;

use Exception;
use Packetery\Core\Api\Rest\Exception\InvalidApiKeyException;
use Packetery\Core\Interfaces\IWebRequestClient;

class PickupPointValidate {

	private const URL_VALIDATE_ENDPOINT = 'https://widget.packeta.com/v6/pps/api/widget/v1/validate';

	/** @var IWebRequestClient */
	private $webRequestClient;

	/** @var string */
	private $apiKey;

	private function __construct( IWebRequestClient $webRequestClient, string $apiKey ) {
		$this->webRequestClient = $webRequestClient;
		$this->apiKey           = $apiKey;
	}

	public static function createWithValidApiKey( IWebRequestClient $webRequestClient, ?string $apiKey ): PickupPointValidate {
		if ( $apiKey === null ) {
			throw InvalidApiKeyException::createFromMissingKey();
		}

		return new self( $webRequestClient, $apiKey );
	}

	/**
	 * @throws RestException
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
			$errors      = is_array( $resultArray['errors'] ) ? $resultArray['errors'] : [];

			if ( isset( $resultArray['status'] ) && in_array( (int) $resultArray['status'], [ 400, 401 ], true ) ) {
				return new PickupPointValidateResponse( true, $errors );
			}

			return new PickupPointValidateResponse( $resultArray['isValid'] ?? false, $errors );
		} catch ( Exception $exception ) {
			throw new RestException( $exception->getMessage() );
		}
	}
}
