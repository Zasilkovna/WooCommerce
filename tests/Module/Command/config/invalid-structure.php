<?php
/**
 * Invalid configuration for testing purposes.
 *
 * This file returns an array representing a form configuration
 * with missing required sections, specifically the 'carriers' key.
 * Used for negative test cases to validate error handling of incomplete configs.
 *
 *  Valid structure:
 *  - global_settings: array of global carrier settings
 *  - carriers: array of carrier configurations (missing in this case)
 *
 * @package Packetery
 */

return [
	'global_settings' => [
		'default_COD_surcharge' => 0,
	],
	// carriers missing
];
