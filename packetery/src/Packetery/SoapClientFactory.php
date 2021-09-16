<?php
/**
 * Class SoapClientFactory.
 *
 * @package Packetery
 */

namespace Packetery;

use Packetery\Api\Soap\Client;
use Packetery\Options\Provider;

/**
 * Class SoapClientFactory.
 *
 * @package Packetery
 */
class SoapClientFactory {
	/**
	 * Options provider.
	 *
	 * @var Provider
	 */
	private $optionsProvider;

	/**
	 * SoapClientFactory constructor.
	 *
	 * @param Provider $optionsProvider Options provider.
	 */
	public function __construct( Provider $optionsProvider ) {
		$this->optionsProvider = $optionsProvider;
	}

	/**
	 * Creates SOAP API client.
	 *
	 * @return Client SOAP API client.
	 */
	public function create() {
		return new Client( $this->optionsProvider->get_api_password() );
	}
}
