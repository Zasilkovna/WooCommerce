<?php

namespace Packetery\Module\Order;

use Packetery\Core\Entity;
use Packetery\Module\Options\OptionsProvider;

class CurrencyConversion {

	private OptionsProvider $optionsProvider;

	public function __construct( OptionsProvider $optionsProvider ) {
		$this->optionsProvider = $optionsProvider;
	}

	/**
	 * List from https://client.packeta.com/cs/packet-drafts
	 *
	 * @return string[]
	 */
	public function getList(): array {
		return [
			'CZK',
			'EUR',
			'HUF',
			'PLN',
			'GBP',
			'RON',
			'UAH',
			'DKK',
			'RUB',
			'CHF',
			'SEK',
			'HKD',
			'USD',
			'CNY',
			'RSD',
			'AED',
			'SGD',
		];
	}

	private function getCurrencyByCountry( string $country ): ?string {
		$mapping = [
			'CZ' => 'CZK',
			'SK' => 'EUR',
			'HU' => 'HUF',
			'PL' => 'PLN',
			'GB' => 'GBP',
			'RO' => 'RON',
			'UA' => 'UAH',
			'DK' => 'DKK',
			'RU' => 'RUB',
			'CH' => 'CHF',
			'SE' => 'SEK',
			'HK' => 'HKD',
			'US' => 'USD',
			'CN' => 'CNY',
			'RS' => 'RSD',
			'AE' => 'AED',
			'SG' => 'SGD',
			'DE' => 'EUR',
			'AT' => 'EUR',
			'FR' => 'EUR',
			'ES' => 'EUR',
			'IT' => 'EUR',
			'SI' => 'EUR',
			'HR' => 'EUR',
			'EE' => 'EUR',
			'LV' => 'EUR',
			'LT' => 'EUR',
			'GR' => 'EUR',
			'IE' => 'EUR',
			'PT' => 'EUR',
			'BE' => 'EUR',
			'NL' => 'EUR',
			'LU' => 'EUR',
			'FI' => 'EUR',
			'CY' => 'EUR',
			'MT' => 'EUR',
		];

		return $mapping[ strtoupper( $country ) ] ?? null;
	}

	/**
	 * @return array{float, string}
	 */
	public function getOrderCustomCurrencyRate( Entity\Order $order ): array {
		$defaultValues = [ 1.0, $order->getCurrency() ];

		if ( ! $this->optionsProvider->isCustomCurrencyRatesEnabled() ) {
			return $defaultValues;
		}

		$country = $order->getShippingCountry();
		if ( $country === null ) {
			return $defaultValues;
		}

		$targetCurrency = $this->getCurrencyByCountry( $country );
		if ( $targetCurrency === null || $targetCurrency === $order->getCurrency() ) {
			return $defaultValues;
		}

		$rate = $this->optionsProvider->getCustomCurrencyRate( $targetCurrency );
		if ( $rate === null || $rate <= 0 ) {
			return $defaultValues;
		}

		return [ $rate, $targetCurrency ];
	}
}
