<?php

declare( strict_types=1 );

namespace Tests\Module\Forms;

use Packetery\Module\Forms\FormService;
use Packetery\Nette\Forms\Form;
use PHPUnit\Framework\TestCase;

class FormServiceTest extends TestCase {

	/**
	 * @var FormService
	 */
	private $formService;

	protected function setUp(): void {
		$this->formService = new FormService();
	}

	/**
	 * @dataProvider provideFormErrorsData
	 */
	public function testFormatFormErrorsToCliMessage( array $formSetup, string $expectedOutput ): void {
		$form = new Form();

		foreach ( $formSetup as $containerName => $fields ) {
			$container = $form->addContainer( $containerName );
			foreach ( $fields as $fieldName => $fieldData ) {
				$field = $container->addText( $fieldName );
				foreach ( $fieldData['errors'] as $error ) {
					$field->addError( $error );
				}
			}
		}

		$actualOutput = $this->formService->formatFormErrorsToCliMessage( $form );
		$this->assertEquals( $expectedOutput, $actualOutput );
	}

	public static function provideFormErrorsData(): array {
		return [
			'single field error'              => [
				'formSetup'      => [
					'container1' => [
						'field1' => [
							'errors' => [ 'Error message 1' ],
						],
					],
				],
				'expectedOutput' => PHP_EOL . 'container1-field1: Error message 1',
			],
			'multiple field errors'           => [
				'formSetup'      => [
					'container1' => [
						'field1' => [
							'errors' => [ 'Error message 1', 'Error message 2' ],
						],
					],
				],
				'expectedOutput' => PHP_EOL . 'container1-field1: Error message 1' . PHP_EOL . 'Error message 2',
			],
			'multiple fields with errors'     => [
				'formSetup'      => [
					'container1' => [
						'field1' => [
							'errors' => [ 'Error message 1' ],
						],
						'field2' => [
							'errors' => [ 'Error message 2' ],
						],
					],
				],
				'expectedOutput' => PHP_EOL . 'container1-field1: Error message 1' . PHP_EOL . 'container1-field2: Error message 2',
			],
			'multiple containers with errors' => [
				'formSetup'      => [
					'container1' => [
						'field1' => [
							'errors' => [ 'Error message 1' ],
						],
					],
					'container2' => [
						'field1' => [
							'errors' => [ 'Error message 2' ],
						],
					],
				],
				'expectedOutput' => PHP_EOL . 'container1-field1: Error message 1' . PHP_EOL . 'container2-field1: Error message 2',
			],
			'no errors'                       => [
				'formSetup'      => [
					'container1' => [
						'field1' => [
							'errors' => [],
						],
					],
				],
				'expectedOutput' => PHP_EOL,
			],
			'nested containers'               => [
				'formSetup'      => [
					'container1'       => [
						'field1' => [
							'errors' => [ 'Error in parent container' ],
						],
					],
					'container1Nested' => [
						'field1' => [
							'errors' => [ 'Error in nested container' ],
						],
					],
				],
				'expectedOutput' => PHP_EOL . 'container1-field1: Error in parent container' . PHP_EOL . 'container1Nested-field1: Error in nested container',
			],
		];
	}
}
