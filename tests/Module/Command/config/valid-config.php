<?php
/**
 * Valid configuration for testing purposes.
 *
 * This file returns an array representing a valid configuration
 * for use in positive test cases.
 *
 * Structure:
 * - global_settings: array of global carrier settings
 * - carriers: array of carrier configurations, each with required fields
 *   such as 'name' and one omitted field
 *
 * @package Packetery
 */

return [
	'global_settings' => [
		'common_setting' => 'value',
	],
	'carriers'        => [
		'1' => [
			'name'             => 'Carrier 1',
			'specific_setting' => 'value1',
		],
		'2' => [
			'name'             => 'Carrier 2',
			'specific_setting' => 'value2',
		],
	],
];
