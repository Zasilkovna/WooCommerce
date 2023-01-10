<?php
/**
 * Class PickupPointValidate
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Rest;

/**
 * Class PickupPointValidate
 *
 * @package Packetery
 */
class PickupPointValidate {

	private const URL_VALIDATE_ENDPOINT = 'https://widget.packeta.com/v6/api/pps/api/widget/validate';

	/**
	 * Downloader.
	 *
	 * @var IDownloader
	 */
	private $downloader;

	/**
	 * API key.
	 *
	 * @var string
	 */
	private $apiKey;

	/**
	 * PickupPointValidate constructor.
	 *
	 * @param IDownloader $downloader Downloader.
	 * @param string      $apiKey API key.
	 */
	public function __construct( IDownloader $downloader, string $apiKey ) {
		$this->downloader = $downloader;
		$this->apiKey     = $apiKey;
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

		try {
			$result      = $this->downloader->post( self::URL_VALIDATE_ENDPOINT, [ 'json' => $postData ] );
			$resultArray = json_decode( $result, true );

			return new PickupPointValidateResponse( $resultArray['isValid'], $resultArray['errors'] );
		} catch ( \Exception $exception ) {
			throw new RestException( $exception->getMessage() );
		}
	}

}
