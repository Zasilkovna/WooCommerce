<?php

declare( strict_types=1 );

namespace Tests\Module\Shipping;

use Packetery\Core\Entity\Carrier;
use Packetery\Latte\Engine;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionsPage;
use Packetery\Module\Checkout\ShippingRateFactory;
use Packetery\Module\CompatibilityBridge;
use Packetery\Module\ContextResolver;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Shipping\BaseShippingMethod;
use Packetery\Module\Shipping\Generated\ShippingMethod_106;
use Packetery\Module\Shipping\Generated\ShippingMethod_10618;
use Packetery\Nette\DI\Container;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

class BaseShippingMethodTest extends TestCase {
	private const METHOD_TITLE     = 'Packeta Home';
	private const OPTIONS_TITLE    = 'Home delivery';
	private const BOX_METHOD_TITLE = 'Packeta Box';

	private const ADMIN_URL                = 'http://example.org/wp-admin/admin.php';
	private const CARRIER_SETTINGS_URL     = 'http://example.org/wp-admin/admin.php?page=packeta-country&carrier_id=106';
	private const BOX_CARRIER_SETTINGS_URL = 'http://example.org/wp-admin/admin.php?page=packeta-country&carrier_id=10618';

	// Output of the template on the base branch for CARRIER_SETTINGS_URL. Latte escapes "&" in the attribute.
	private const SETTINGS_LINK_HTML = "<p>\n"
		. "\t<a href=\"http://example.org/wp-admin/admin.php?page=packeta-country&amp;carrier_id=106\" target=\"_blank\">\n"
		. "\t\tConfigure selected carrier\n"
		. "\t</a>\n"
		. "</p>\n";

	// Output of the template for BOX_CARRIER_SETTINGS_URL.
	private const BOX_SETTINGS_LINK_HTML = "<p>\n"
		. "\t<a href=\"http://example.org/wp-admin/admin.php?page=packeta-country&amp;carrier_id=10618\""
		. " target=\"_blank\">\n"
		. "\t\tConfigure selected carrier\n"
		. "\t</a>\n"
		. "</p>\n";

	private mixed $originalContainer;

	protected function setUp(): void {
		$this->originalContainer = self::getContainerProperty()->getValue();
	}

	protected function tearDown(): void {
		self::getContainerProperty()->setValue( null, $this->originalContainer );
	}

	private static function getContainerProperty(): ReflectionProperty {
		return new ReflectionProperty( CompatibilityBridge::class, 'diContainer' );
	}

	public function testConstructorTakesMethodTitleFromCarrier(): void {
		$carrier = $this->createMock( Carrier::class );
		$carrier
			->method( 'getName' )
			->willReturn( self::BOX_METHOD_TITLE );

		// The first call gives the method title. The WordPress stub of __() returns null,
		// so the second call gives no carrier and the method description does not use sprintf().
		$carrierRepository = $this->createMock( EntityRepository::class );
		$carrierRepository
			->expects( $this->exactly( 2 ) )
			->method( 'getAnyById' )
			->with( ShippingMethod_10618::CARRIER_ID, true )
			->willReturnOnConsecutiveCalls( $carrier, null );

		$contextResolver = $this->createMock( ContextResolver::class );
		$contextResolver
			->method( 'isCarrierSettingsLinkContext' )
			->willReturn( false );

		$services  = [
			EntityRepository::class    => $carrierRepository,
			WpAdapter::class           => $this->createMock( WpAdapter::class ),
			ContextResolver::class     => $contextResolver,
			ShippingRateFactory::class => $this->createMock( ShippingRateFactory::class ),
		];
		$container = $this->createMock( Container::class );
		$container
			->method( 'getByType' )
			->willReturnCallback(
				function ( string $type ) use ( $services ): object {
					return $services[ $type ];
				}
			);
		self::getContainerProperty()->setValue( null, $container );

		$shippingMethod = new ShippingMethod_10618();

		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		self::assertSame( self::BOX_METHOD_TITLE, $shippingMethod->method_title );
	}

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
	 * @return array<string, array{
	 *     contexts: array<int, bool>,
	 *     expectedDescriptions: array<int, string>,
	 *     expectedRenderCount: int
	 * }>
	 */
	public static function settingsLinkContextProvider(): array {
		return [
			'not allowed'               => [
				'contexts'             => [ false, false ],
				'expectedDescriptions' => [ '', '' ],
				'expectedRenderCount'  => 0,
			],
			'not allowed, then allowed' => [
				'contexts'             => [ false, true ],
				'expectedDescriptions' => [ '', self::SETTINGS_LINK_HTML ],
				'expectedRenderCount'  => 1,
			],
			'allowed, then not allowed' => [
				'contexts'             => [ true, false ],
				'expectedDescriptions' => [ self::SETTINGS_LINK_HTML, '' ],
				'expectedRenderCount'  => 1,
			],
			'allowed two times'         => [
				'contexts'             => [ true, true ],
				'expectedDescriptions' => [ self::SETTINGS_LINK_HTML, self::SETTINGS_LINK_HTML ],
				'expectedRenderCount'  => 1,
			],
		];
	}

	/**
	 * @dataProvider settingsLinkContextProvider
	 *
	 * @param array<int, bool>   $contexts             Context of each call: true if the link is allowed.
	 * @param array<int, string> $expectedDescriptions Expected description of the link field for each call.
	 * @param int                $expectedRenderCount  Expected count of template renders.
	 */
	public function testSettingsLinkIsRenderedOnlyInAllowedContext(
		array $contexts,
		array $expectedDescriptions,
		int $expectedRenderCount
	): void {
		$latteEngine     = $this->createCountingLatteEngine();
		$contextResolver = $this->createMock( ContextResolver::class );
		$contextResolver
			->method( 'isCarrierSettingsLinkContext' )
			->willReturnOnConsecutiveCalls( ...$contexts );
		$wpAdapter = $this->createSettingsLinkWpAdapter( $expectedRenderCount );
		$wpAdapter
			->method( 'determineLocale' )
			->willReturn( self::createUniqueLocale() );

		$shippingMethod = $this->createSettingsLinkShippingMethod(
			ShippingMethod_106::class,
			$latteEngine,
			$wpAdapter,
			$contextResolver
		);

		$descriptions = [];
		foreach ( $contexts as $context ) {
			$descriptions[] = $shippingMethod->get_instance_form_fields()['custom_html']['description'];
		}

		self::assertSame( $expectedDescriptions, $descriptions );
		self::assertSame( $expectedRenderCount, $latteEngine->renderCount );
	}

	public function testSettingsLinkIsRenderedOncePerCarrier(): void {
		$latteEngine = $this->createCountingLatteEngine();
		$wpAdapter   = $this->createSettingsLinkWpAdapter( 2 );
		$wpAdapter
			->method( 'determineLocale' )
			->willReturn( self::createUniqueLocale() );
		$contextResolver = $this->createAllowingContextResolver();

		$createShippingMethod = fn( string $className ): BaseShippingMethod => $this->createSettingsLinkShippingMethod(
			$className,
			$latteEngine,
			$wpAdapter,
			$contextResolver
		);

		$firstShippingMethod  = $createShippingMethod( ShippingMethod_106::class );
		$secondShippingMethod = $createShippingMethod( ShippingMethod_106::class );
		$boxShippingMethod    = $createShippingMethod( ShippingMethod_10618::class );

		$firstFormFields = $firstShippingMethod->get_instance_form_fields();
		$descriptions    = [
			$firstFormFields['custom_html']['description'],
			$firstShippingMethod->get_instance_form_fields()['custom_html']['description'],
			$secondShippingMethod->get_instance_form_fields()['custom_html']['description'],
			$boxShippingMethod->get_instance_form_fields()['custom_html']['description'],
		];

		self::assertSame( self::METHOD_TITLE, $firstFormFields['title']['default'] );
		self::assertSame(
			[
				self::SETTINGS_LINK_HTML,
				self::SETTINGS_LINK_HTML,
				self::SETTINGS_LINK_HTML,
				self::BOX_SETTINGS_LINK_HTML,
			],
			$descriptions
		);
		self::assertSame( 2, $latteEngine->renderCount );
	}

	public function testSettingsLinkIsRenderedOncePerLocale(): void {
		$latteEngine  = $this->createCountingLatteEngine();
		$wpAdapter    = $this->createSettingsLinkWpAdapter( 2 );
		$firstLocale  = self::createUniqueLocale();
		$secondLocale = self::createUniqueLocale();
		$wpAdapter
			->method( 'determineLocale' )
			->willReturnOnConsecutiveCalls( $firstLocale, $firstLocale, $secondLocale );

		$shippingMethod = $this->createSettingsLinkShippingMethod(
			ShippingMethod_106::class,
			$latteEngine,
			$wpAdapter,
			$this->createAllowingContextResolver()
		);

		$descriptions = [
			$shippingMethod->get_instance_form_fields()['custom_html']['description'],
			$shippingMethod->get_instance_form_fields()['custom_html']['description'],
			$shippingMethod->get_instance_form_fields()['custom_html']['description'],
		];

		self::assertSame( array_fill( 0, 3, self::SETTINGS_LINK_HTML ), $descriptions );
		self::assertSame( 2, $latteEngine->renderCount );
	}

	/**
	 * The cache is a static variable of the method, so a test cannot reset it.
	 * A unique locale gives each test its own cache entry.
	 */
	private static function createUniqueLocale(): string {
		return uniqid( 'en_US_', true );
	}

	/**
	 * @return Engine&object{renderCount: int}
	 */
	private function createCountingLatteEngine(): Engine {
		return new class() extends Engine {
			public int $renderCount = 0;

			/**
			 * @param string                   $name   Template file.
			 * @param object|array<mixed>|null $params Template parameters.
			 * @param string|null              $block  Template block.
			 */
			public function renderToString( string $name, $params = [], ?string $block = null ): string {
				++$this->renderCount;

				return parent::renderToString( $name, $params, $block );
			}
		};
	}

	private function createAllowingContextResolver(): ContextResolver&MockObject {
		$contextResolver = $this->createMock( ContextResolver::class );
		$contextResolver
			->method( 'isCarrierSettingsLinkContext' )
			->willReturn( true );

		return $contextResolver;
	}

	/**
	 * @param int $renderCount Expected count of renders. Each render builds the link one time.
	 */
	private function createSettingsLinkWpAdapter( int $renderCount ): WpAdapter&MockObject {
		$wpAdapter = $this->createMock( WpAdapter::class );
		$wpAdapter
			->expects( $this->exactly( $renderCount ) )
			->method( 'getAdminUrl' )
			->with( null, 'admin.php' )
			->willReturn( self::ADMIN_URL );
		$wpAdapter
			->expects( $this->exactly( $renderCount ) )
			->method( 'addQueryArg' )
			->willReturnMap(
				[
					[
						self::createSettingsLinkQuery( ShippingMethod_106::CARRIER_ID ),
						self::ADMIN_URL,
						self::CARRIER_SETTINGS_URL,
					],
					[
						self::createSettingsLinkQuery( ShippingMethod_10618::CARRIER_ID ),
						self::ADMIN_URL,
						self::BOX_CARRIER_SETTINGS_URL,
					],
				]
			);
		$wpAdapter
			->method( '__' )
			->willReturnArgument( 0 );

		return $wpAdapter;
	}

	/**
	 * @return array<string, string>
	 */
	private static function createSettingsLinkQuery( string $carrierId ): array {
		return [
			'page'                            => OptionsPage::SLUG,
			OptionsPage::PARAMETER_CARRIER_ID => $carrierId,
		];
	}

	/**
	 * @param string          $className       Class name of a BaseShippingMethod subclass.
	 * @param Engine          $latteEngine     Latte engine.
	 * @param WpAdapter       $wpAdapter       WP adapter.
	 * @param ContextResolver $contextResolver Context resolver.
	 */
	private function createSettingsLinkShippingMethod(
		string $className,
		Engine $latteEngine,
		WpAdapter $wpAdapter,
		ContextResolver $contextResolver
	): BaseShippingMethod {
		$services  = [
			Engine::class          => $latteEngine,
			ContextResolver::class => $contextResolver,
		];
		$container = $this->createMock( Container::class );
		$container
			->method( 'getByType' )
			->willReturnCallback(
				function ( string $type ) use ( $services ): object {
					return $services[ $type ];
				}
			);

		$shippingMethod = ( new ReflectionClass( $className ) )->newInstanceWithoutConstructor();
		// phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
		$shippingMethod->method_title = self::METHOD_TITLE;
		$this->setBaseShippingMethodProperty( $shippingMethod, 'container', $container );
		$this->setBaseShippingMethodProperty( $shippingMethod, 'wpAdapter', $wpAdapter );

		return $shippingMethod;
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
