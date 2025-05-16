<?php
/**
 * Invalid configuration for testing purposes.
 *
 * This file returns an array representing a form configuration
 * with missing required fields and invalid data, intended for
 * use in negative test cases.
 *
 * Structure:
 * - global_settings: array of global carrier settings (empty in this case)
 * - carriers: array of carrier configurations, where required fields
 *   such as 'name' may be missing or replaced with invalid data
 *
 * @package Packetery
 */

return [
	'global_settings' => [],
	'carriers'        => [
		'1' => [
			// name is required
			'invalid' => 'data',
		],
	],
];
