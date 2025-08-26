<?php

declare( strict_types=1 );

namespace Tests\Module;

use DateTimeImmutable;
use Packetery\Core\CoreHelper;
use Packetery\Module\FormValidators;
use Packetery\Nette\Forms\Controls\TextInput;
use PHPUnit\Framework\TestCase;

class FormValidatorsTest extends TestCase {
	/**
	 * @return array<int, array<int, array<int|string|float|bool|null>>>
	 */
	public static function validatorsDataProvider(): array {
		return [
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				'20:20',
				true,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				'00:00',
				true,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				'000:00',
				false,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				'00:000',
				false,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				'000:000',
				false,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				'01:08',
				true,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				'23:59',
				true,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				'0:00',
				false,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				'30:00',
				false,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				'20:60',
				false,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				':',
				false,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				'00:0',
				false,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				'',
				false,
			],
			[
				[ FormValidators::class, 'hasClockTimeFormat' ],
				[],
				null,
				false,
			],
			[
				[ FormValidators::class, 'greaterThan' ],
				[ 100 ],
				200,
				true,
			],
			[
				[ FormValidators::class, 'greaterThan' ],
				[ 100.1 ],
				100.2,
				true,
			],
			[
				[ FormValidators::class, 'greaterThan' ],
				[ 100.01 ],
				100.1,
				true,
			],
			[
				[ FormValidators::class, 'greaterThan' ],
				[ 100.3 ],
				100.29,
				false,
			],
			[
				[ FormValidators::class, 'greaterThan' ],
				[ 0 ],
				0,
				false,
			],
			[
				[ FormValidators::class, 'greaterThan' ],
				[ 0.001 ],
				0,
				false,
			],
			[
				[ FormValidators::class, 'greaterThan' ],
				[ 0.001 ],
				'',
				false,
			],
			[
				[ FormValidators::class, 'greaterThan' ],
				[ 0.001 ],
				null,
				false,
			],
			[
				[ FormValidators::class, 'greaterThan' ],
				[ 0 ],
				0.001,
				true,
			],
			[
				[ FormValidators::class, 'greaterThan' ],
				[ - 100.3 ],
				- 100.29,
				true,
			],
			[
				[ FormValidators::class, 'dateIsLater' ],
				[
					DateTimeImmutable::createFromFormat( CoreHelper::DATEPICKER_FORMAT, '2020-01-01' )
									->modify( '- 1 day' )
									->format( CoreHelper::DATEPICKER_FORMAT ),
				],
				'2020-01-01',
				true,
			],
			[
				[ FormValidators::class, 'dateIsLater' ],
				[ '2020-12-31' ],
				'2021-01-01',
				true,
			],
			[
				[ FormValidators::class, 'dateIsLater' ],
				[ '2020-12-31' ],
				'2020-01-01',
				false,
			],
			[
				[ FormValidators::class, 'dateIsLater' ],
				[ '2020-01-01' ],
				'2020-01-01',
				false,
			],
			[
				[ FormValidators::class, 'dateIsLater' ],
				[ '2020-01-02' ],
				'2020-01-01',
				false,
			],
			[
				[ FormValidators::class, 'dateIsLater' ],
				[ '2020-01-02' ],
				'',
				false,
			],
			[
				[ FormValidators::class, 'dateIsLater' ],
				[ '2020-01-02' ],
				null,
				false,
			],
			[
				[ FormValidators::class, 'dateIsInMysqlFormat' ],
				[],
				'2020-01-01',
				true,
			],
			[
				[ FormValidators::class, 'dateIsInMysqlFormat' ],
				[],
				'2020-12-31',
				true,
			],
			[
				[ FormValidators::class, 'dateIsInMysqlFormat' ],
				[],
				'9999-01-01',
				true,
			],
			[
				[ FormValidators::class, 'dateIsInMysqlFormat' ],
				[],
				'2020.01.01',
				false,
			],
			[
				[ FormValidators::class, 'dateIsInMysqlFormat' ],
				[],
				'01.01.2020',
				false,
			],
			[
				[ FormValidators::class, 'dateIsInMysqlFormat' ],
				[],
				'2020-22-01',
				false,
			],
			[
				[ FormValidators::class, 'dateIsInMysqlFormat' ],
				[],
				'2020-11-33',
				false,
			],
			[
				[ FormValidators::class, 'dateIsInMysqlFormat' ],
				[],
				'2020-11-11 00:00:00',
				false,
			],
			[
				[ FormValidators::class, 'dateIsInMysqlFormat' ],
				[],
				'',
				false,
			],
			[
				[ FormValidators::class, 'dateIsInMysqlFormat' ],
				[],
				null,
				false,
			],
		];
	}

	/**
	 * @param callable(TextInput): bool    $validator
	 * @param array<string|float|int|null> $validatorArgs
	 *
	 * @dataProvider validatorsDataProvider
	 */
	public function testValidators( callable $validator, array $validatorArgs, string|float|int|null $inputValue, bool $expected ): void {
		$input = new TextInput();
		$input->setValue( $inputValue );

		$result = $validator( $input, ...$validatorArgs );
		$this->assertSame( $expected, $result );
	}
}
