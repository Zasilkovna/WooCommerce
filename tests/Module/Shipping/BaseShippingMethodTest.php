<?php

declare( strict_types=1 );

namespace Tests\Module\Shipping;

use Packetery\Latte\Engine;
use Packetery\Module\Checkout\ShippingRateFactory;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Shipping\BaseShippingMethod;
use Packetery\Module\Shipping\Generated\ShippingMethod_106;
use Packetery\Module\Shipping\Generated\ShippingMethod_10618;
use Packetery\Nette\DI\Container;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class BaseShippingMethodTest extends TestCase {
	private const METHOD_TITLE     = 'Packeta Home';
	private const OPTIONS_TITLE    = 'Home delivery';
	private const BOX_METHOD_TITLE = 'Packeta Box';

	private const SETTINGS_LINK_HTML = '<p><a href="carrier-settings">Configure selected carrier</a></p>';

	/**
	 * @return array<string, array{
	 *     className: string,
	 *     title: string,
	 *     options: array<string, string|null>|false|null,
	 *     expectedCarrierName: string
	 * }>
	 */
	public static function carrierNameProvider(): array {
		return [
			'title of the method'            => [
				'className'           => ShippingMethod_106::class,
				'title'               => self::METHOD_TITLE,
				'options'             => null,
				'expectedCarrierName' => self::METHOD_TITLE,
			],
			'options title takes precedence' => [
				'className'           => ShippingMethod_106::class,
				'title'               => self::METHOD_TITLE,
				'options'             => [ 'title' => self::OPTIONS_TITLE ],
				'expectedCarrierName' => self::OPTIONS_TITLE,
			],
			'empty options title is kept'    => [
				'className'           => ShippingMethod_106::class,
				'title'               => self::METHOD_TITLE,
				'options'             => [ 'title' => '' ],
				'expectedCarrierName' => '',
			],
			'options not saved yet'          => [
				'className'           => ShippingMethod_106::class,
				'title'               => self::METHOD_TITLE,
				'options'             => false,
				'expectedCarrierName' => self::METHOD_TITLE,
			],
			'carrier id of the method class' => [
				'className'           => ShippingMethod_10618::class,
				'title'               => self::BOX_METHOD_TITLE,
				'options'             => null,
				'expectedCarrierName' => self::BOX_METHOD_TITLE,
			],
		];
	}

	/**
	 * @dataProvider carrierNameProvider
	 *
	 * @param string                                $className           Class name of a BaseShippingMethod subclass.
	 * @param string                                $title               Title returned by get_title().
	 * @param array<string, string|null>|false|null $options             Method options; null leaves the property at its default.
	 * @param string                                $expectedCarrierName Carrier name passed to the rate factory.
	 */
	public function testCalculateShippingPassesOwnCarrierName(
		string $className,
		string $title,
		array|false|null $options,
		string $expectedCarrierName
	): void {
		$shippingRateFactory = $this->createMock( ShippingRateFactory::class );
		$shippingRateFactory
			->expects( $this->once() )
			->method( 'createShippingRates' )
			->with(
				$this->identicalTo( $expectedCarrierName ),
				$this->identicalTo( $className::getShippingMethodId() ),
				$this->anything()
			)
			->willReturn( [] );

		$shippingMethod = $this->createShippingMethod( $className, $title, $shippingRateFactory );
		if ( $options !== null ) {
			$this->setBaseShippingMethodProperty( $shippingMethod, 'options', $options );
		}

		$shippingMethod->calculate_shipping( [] );
	}

	public function testCalculateShippingAddsEachRate(): void {
		$firstRate  = [
			'id'    => 'packeta_method_106:5',
			'label' => 'Packeta Home',
			'cost'  => 100.0,
		];
		$secondRate = [
			'id'    => 'packeta_method_106:6',
			'label' => 'Packeta Home Express',
			'cost'  => 150.0,
		];

		$shippingRateFactory = $this->createMock( ShippingRateFactory::class );
		$shippingRateFactory
			->method( 'createShippingRates' )
			->willReturn(
				[
					$firstRate['id']  => $firstRate,
					$secondRate['id'] => $secondRate,
				]
			);

		$shippingMethod = $this->createShippingMethod( ShippingMethod_106::class, self::METHOD_TITLE, $shippingRateFactory );
		$addedRates     = [];
		$shippingMethod
			->expects( $this->exactly( 2 ) )
			->method( 'add_rate' )
			->willReturnCallback(
				function ( array $rate ) use ( &$addedRates ): void {
					$addedRates[] = $rate;
				}
			);

		$shippingMethod->calculate_shipping( [] );

		self::assertSame( [ $firstRate, $secondRate ], $addedRates );
	}

	/**
	 * @return array<string, array{isAdmin: bool, renderCount: int, expectedDescription: string}>
	 */
	public static function settingsLinkProvider(): array {
		return [
			'admin renders the link'      => [
				'isAdmin'             => true,
				'renderCount'         => 1,
				'expectedDescription' => self::SETTINGS_LINK_HTML,
			],
			'storefront skips the render' => [
				'isAdmin'             => false,
				'renderCount'         => 0,
				'expectedDescription' => '',
			],
		];
	}

	/**
	 * @dataProvider settingsLinkProvider
	 *
	 * @param bool   $isAdmin             Whether the request is in the admin.
	 * @param int    $renderCount         Expected number of template renders.
	 * @param string $expectedDescription Expected description of the settings link field.
	 */
	public function testInstanceFormFieldsRenderSettingsLinkOnlyInAdmin(
		bool $isAdmin,
		int $renderCount,
		string $expectedDescription
	): void {
		$latteEngine = $this->createMock( Engine::class );
		$latteEngine
			->expects( $this->exactly( $renderCount ) )
			->method( 'renderToString' )
			->willReturn( self::SETTINGS_LINK_HTML );

		$container = $this->createMock( Container::class );
		$container
			->method( 'getByType' )
			->willReturn( $latteEngine );

		$wpAdapter = $this->createMock( WpAdapter::class );
		$wpAdapter
			->method( 'isAdmin' )
			->willReturn( $isAdmin );

		$shippingMethod = ( new ReflectionClass( ShippingMethod_106::class ) )->newInstanceWithoutConstructor();
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$shippingMethod->method_title = self::METHOD_TITLE;
		$this->setBaseShippingMethodProperty( $shippingMethod, 'container', $container );
		$this->setBaseShippingMethodProperty( $shippingMethod, 'wpAdapter', $wpAdapter );

		$formFields = $shippingMethod->get_instance_form_fields();

		self::assertSame( $expectedDescription, $formFields['custom_html']['description'] );
		self::assertSame( self::METHOD_TITLE, $formFields['title']['default'] );
	}

	/**
	 * @param string              $className           Class name of a BaseShippingMethod subclass.
	 * @param string              $title               Title returned by get_title().
	 * @param ShippingRateFactory $shippingRateFactory Shipping rate factory.
	 */
	private function createShippingMethod(
		string $className,
		string $title,
		ShippingRateFactory $shippingRateFactory
	): BaseShippingMethod&MockObject {
		$shippingMethod = $this->getMockBuilder( $className )
			->disableOriginalConstructor()
			->onlyMethods( [ 'add_rate', 'get_title' ] )
			->getMock();
		$shippingMethod
			->method( 'get_title' )
			->willReturn( $title );
		$shippingMethod->id = $className::getShippingMethodId();

		// A rate needs no service from the container, so the method cannot load the shipping zone.
		$container = $this->createMock( Container::class );
		$container
			->expects( $this->never() )
			->method( 'getByType' );

		$this->setBaseShippingMethodProperty( $shippingMethod, 'container', $container );
		$this->setBaseShippingMethodProperty( $shippingMethod, 'shippingRateFactory', $shippingRateFactory );

		return $shippingMethod;
	}

	/**
	 * @param BaseShippingMethod $shippingMethod Shipping method instance.
	 * @param string             $name           Property name.
	 * @param mixed              $value          Property value.
	 */
	private function setBaseShippingMethodProperty( BaseShippingMethod $shippingMethod, string $name, mixed $value ): void {
		$property = ( new ReflectionClass( BaseShippingMethod::class ) )->getProperty( $name );
		$property->setAccessible( true );
		$property->setValue( $shippingMethod, $value );
	}
}
