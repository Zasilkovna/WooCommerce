<?php
/**
 * Scoper config.
 *
 * @package Packetery
 */

/**
 * Customizes PHP scoper config.
 *
 * @param array $config Config.
 *
 * @return array
 */
function customize_php_scoper_config( array $config ): array {
	$config['patchers'][] = static function ( string $filePath, string $prefix, string $content ): string {
		$regexForPrefix = "([\\s\"'\\[(<@|])";
		if ( str_contains( $filePath, 'latte/latte' ) ) {
			// would not work for other imported namespace
			// e.g.: use Nette\Bridges\Latte; echo Latte\SomeClass::method();
			// luckily Nette related packages do not do that
			$content = preg_replace(
				[
					"~{$regexForPrefix}\\\\\\\\Latte\\\\\\\\~",
					"~{$regexForPrefix}Latte\\\\\\\\~",
					"~{$regexForPrefix}\\\\Latte\\\\~",
					"~{$regexForPrefix}Latte\\\\~",
				],
				[
					'$1\\\\\\\\Packetery\\\\\\\\Latte\\\\\\\\',
					'$1\\\\\\\\Packetery\\\\\\\\Latte\\\\\\\\',
					'$1\\\\Packetery\\\\Latte\\\\',
					'$1\\\\Packetery\\\\Latte\\\\',
				],
				$content
			);

			$content = str_replace( [
				'namespace \\Packetery',
				'use Tracy\\Dumper;',
				'use Tracy\\Helpers;',
			], [
				// would not work in string context
				'namespace Packetery',
				'use \\Packetery\\Tracy\\Dumper;',
				'use \\Packetery\\Tracy\\Helpers;',
			], $content );
		}

		if ( str_contains( $filePath, 'nette/' ) ) {
			$content = preg_replace(
				[
					"~{$regexForPrefix}\\\\\\\\Nette\\\\\\\\~",
					"~{$regexForPrefix}Nette\\\\\\\\~",
					"~{$regexForPrefix}\\\\Nette\\\\~",
					"~{$regexForPrefix}Nette\\\\~",
				],
				[
					'$1\\\\\\\\Packetery\\\\\\\\Nette\\\\\\\\',
					'$1\\\\\\\\Packetery\\\\\\\\Nette\\\\\\\\',
					'$1\\\\Packetery\\\\Nette\\\\',
					'$1\\\\Packetery\\\\Nette\\\\',
				],
				$content
			);

			$content = str_replace( [
				'namespace \\Packetery',
				'use Nette;',
				'use Tracy\\Dumper;',
				'use Tracy\\Helpers;',
			], [
				// would not work in string context
				'namespace Packetery',
				'use \\Packetery\\Nette;',
				'use \\Packetery\\Tracy\\Dumper;',
				'use \\Packetery\\Tracy\\Helpers;',
			], $content );
		}

		if ( str_contains( $filePath, 'tracy/tracy/' ) ) {
			$content = preg_replace(
				[
					"~{$regexForPrefix}\\\\\\\\Tracy\\\\\\\\~",
					"~{$regexForPrefix}Tracy\\\\\\\\~",
					"~{$regexForPrefix}\\\\Tracy\\\\~",
					"~{$regexForPrefix}Tracy\\\\~",
				],
				[
					'$1\\\\\\\\Packetery\\\\\\\\Tracy\\\\\\\\',
					'$1\\\\\\\\Packetery\\\\\\\\Tracy\\\\\\\\',
					'$1\\\\Packetery\\\\Tracy\\\\',
					'$1\\\\Packetery\\\\Tracy\\\\',
				],
				$content
			);

			$content = str_replace(
				[
					'namespace \\Packetery',
					'namespace Tracy',
				],
				[
					// would not work in string context
					'namespace Packetery',
					'namespace Packetery\\Tracy',
				],
				$content
			);
		}

		return $content;
	};

	return $config;
}
