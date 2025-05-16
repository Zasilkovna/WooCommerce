<?php

declare( strict_types=1 );

namespace Packetery\Module\Forms;

use Packetery\Nette\Forms\Container;
use Packetery\Nette\Forms\Controls\BaseControl;

class FormService {
	/**
	 * @return array<string, array<string>>
	 */
	private function extractFormComponentErrors( Container $container, string $parentName ): array {
		$errors = [];

		/** @var BaseControl|Container $component */
		foreach ( $container->getComponents() as $component ) {
			if ( $component->getErrors() === [] ) {
				continue;
			}

			$prefix = $parentName !== '' ? $parentName . '-' : '';
			if ( $component instanceof Container ) {
				$errors = array_merge(
					$errors,
					$this->extractFormComponentErrors(
						$component,
						$prefix . $component->getName()
					)
				);

				continue;
			}

			$errors[ $prefix . $component->getName() ] = $component->getErrors();
		}

		return $errors;
	}

	public function formatFormErrorsToCliMessage( Container $container ): string {
		$errors          = $this->extractFormComponentErrors( $container, '' );
		$formattedErrors = [];
		foreach ( $errors as $componentName => $componentErrors ) {
			$formattedErrors[] = $componentName . ': ' . implode( PHP_EOL, $componentErrors );
		}

		return PHP_EOL . implode( PHP_EOL, $formattedErrors );
	}
}
