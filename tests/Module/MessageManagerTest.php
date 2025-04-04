<?php

declare( strict_types=1 );

namespace Tests\Module;

use Packetery\Latte\Engine;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Message;
use Packetery\Module\MessageManager;
use PHPUnit\Framework\TestCase;

class MessageManagerTest extends TestCase {
	public function testFlashing(): void {
		$wpAdapter = $this->createMock( WpAdapter::class );
		$wpAdapter
			->method( 'getTransient' )
			->willReturn( false );

		$messageManager = new MessageManager(
			$this->createLatteEngineMock(),
			$wpAdapter
		);

		$message = Message::create()
							->setContext( 'main' )
							->setType( MessageManager::TYPE_SUCCESS )
							->setText( 'Success message' )
							->setEscape( false )
							->setRenderer( MessageManager::RENDERER_WORDPRESS );
		$messageManager->flashMessageObject( $message );

		$result = $messageManager->renderToString( MessageManager::RENDERER_PACKETERY, 'main' );
		$this->assertSame( '', $result );
		$result = $messageManager->renderToString( MessageManager::RENDERER_WORDPRESS, 'other' );
		$this->assertSame( '', $result );
		$result = $messageManager->renderToString( MessageManager::RENDERER_WORDPRESS, 'main' );
		$this->assertNotSame( '', $result );
		$this->assertStringContainsString( $this->latteEngineStringRenderer( $message->toArray() ), $result );

		$result = $messageManager->renderToString( MessageManager::RENDERER_WORDPRESS, 'main' );
		$this->assertSame( '', $result );

		$message = 'Some message 123';
		$messageManager->flash_message( $message );
		$result = $messageManager->renderToString( MessageManager::RENDERER_WORDPRESS, '' );
		$this->assertStringContainsString( $message, $result );
	}

	public function testRender(): void {
		$messageManager = new MessageManager(
			$this->createLatteEngineMock(),
			$this->createMock( WpAdapter::class )
		);

		$expectedMessage = 'Some message 123';
		$messageManager->flash_message( $expectedMessage );

		$this->expectOutputRegex( '~' . preg_quote( $expectedMessage, '~' ) . '~' );
		$messageManager->render( MessageManager::RENDERER_WORDPRESS, '' );
	}

	private function createLatteEngineMock(): Engine {
		$latteEngine = $this->createMock( Engine::class );
		$latteEngine
			->method( 'renderToString' )
			->willReturnCallback(
				function ( string $name, array $params ): string {
					return $this->latteEngineStringRenderer( $params );
				}
			);

		return $latteEngine;
	}

	private function latteEngineStringRenderer( array $params ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
		return json_encode( $params );
	}
}
