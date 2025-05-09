<?php

namespace Packetery\Module\Commands;

class ShippingAdreessDataFixtures {

	public const ADDRESS_AE = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Sheikh Zayed Rd',
		'city'       => 'Dubai',
		'postcode'   => '00000',
		'country'    => 'AE',
	];

	public const ADDRESS_AT = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Mariahilfer Straße 10',
		'city'       => 'Wien',
		'postcode'   => '1060',
		'country'    => 'AT',
	];

	public const ADDRESS_BE = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Rue Neuve 123',
		'city'       => 'Bruxelles',
		'postcode'   => '1000',
		'country'    => 'BE',
	];

	public const ADDRESS_BG = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'bul. Vitosha 5',
		'city'       => 'Sofia',
		'postcode'   => '1000',
		'country'    => 'BG',
	];

	public const ADDRESS_CH = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Bahnhofstrasse 1',
		'city'       => 'Zürich',
		'postcode'   => '8001',
		'country'    => 'CH',
	];

	public const ADDRESS_CY = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Makariou Avenue 25',
		'city'       => 'Nicosia',
		'postcode'   => '1010',
		'country'    => 'CY',
	];

	public const ADDRESS_CZ = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Vodičkova 10',
		'city'       => 'Praha',
		'postcode'   => '11000',
		'country'    => 'CZ',
	];

	public const ADDRESS_DE = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Unter den Linden 77',
		'city'       => 'Berlin',
		'postcode'   => '10117',
		'country'    => 'DE',
	];

	public const ADDRESS_DK = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Strøget 15',
		'city'       => 'Copenhagen',
		'postcode'   => '1550',
		'country'    => 'DK',
	];

	public const ADDRESS_EE = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Narva mnt 5',
		'city'       => 'Tallinn',
		'postcode'   => '10117',
		'country'    => 'EE',
	];

	public const ADDRESS_ES = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Gran Via 1',
		'city'       => 'Madrid',
		'postcode'   => '28013',
		'country'    => 'ES',
	];

	public const ADDRESS_FI = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Mannerheimintie 20',
		'city'       => 'Helsinki',
		'postcode'   => '00100',
		'country'    => 'FI',
	];

	public const ADDRESS_FR = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Champs-Élysées 50',
		'city'       => 'Paris',
		'postcode'   => '75008',
		'country'    => 'FR',
	];

	public const ADDRESS_GB = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => '221B Baker Street',
		'city'       => 'London',
		'postcode'   => 'NW1 6XE',
		'country'    => 'GB',
	];

	public const ADDRESS_GR = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Ermou 56',
		'city'       => 'Athens',
		'postcode'   => '10563',
		'country'    => 'GR',
	];

	public const ADDRESS_HR = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Ilica 10',
		'city'       => 'Zagreb',
		'postcode'   => '10000',
		'country'    => 'HR',
	];

	public const ADDRESS_HU = [
		'first_name' => 'John',
		'last_name'  => 'Packeta',
		'address_1'  => 'Andrássy út 1',
		'city'       => 'Budapest',
		'postcode'   => '1061',
		'country'    => 'HU',
	];

	public const ADDRESS_DEFAULT = self::ADDRESS_CZ;

	public static function getAddressByCountry( string $countryCode ): array {
		$constName = 'self::ADDRESS_' . strtoupper( $countryCode );

		if ( defined( $constName ) ) {
			return constant( $constName );
		}

		return self::ADDRESS_DEFAULT;
	}

	public static function getPhoneByCountry( string $countryCode ): string {
		$numbers = [
			'CZ' => '+420789789789',
			'SK' => '+421912912912',
			'HU' => '+36701701701',
			'PL' => '+48601234567',
			'RO' => '+40745678123',
			'AT' => '+436601234567',
			'DE' => '+491601234567',
			'FR' => '+33612345678',
			'IT' => '+393491234567',
			'GB' => '+447700900123',
			'UA' => '+380671234567',
			'LT' => '+37061234567',
			'LV' => '+37121234567',
			'EE' => '+37251234567',
			'SI' => '+38640123456',
			'HR' => '+385921234567',
			'BG' => '+359881234567',
			'FI' => '+358401234567',
			'SE' => '+46701234567',
			'DK' => '+4520123456',
			'NL' => '+31612345678',
			'BE' => '+32470123456',
			'LU' => '+352621123456',
			'IE' => '+353851234567',
			'PT' => '+351912345678',
			'ES' => '+34612345678',
			'GR' => '+306912345678',
			'TR' => '+905301234567',
			'US' => '+12025550123',
			'AE' => '+971501234567',
		];

		$code = strtoupper( $countryCode );

		return $numbers[ $code ] ?? '+999123456789';
	}
}
