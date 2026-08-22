<?php
/**
 * Class ReturnFormShortcode.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Returns;

use Packetery\Latte\Engine;
use Packetery\Module\Api\Internal\ReturnRouter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Views\AssetManager;

/**
 * Renders the guest return form via the [packeta_return_form] shortcode. The guest is verified on
 * submit by order number and matching billing e-mail; eligibility is enforced server-side.
 */
class ReturnFormShortcode {

	private WpAdapter $wpAdapter;
	private Engine $latteEngine;
	private OptionsProvider $optionsProvider;
	private ReturnRouter $returnRouter;
	private AssetManager $assetManager;

	public function __construct(
		WpAdapter $wpAdapter,
		Engine $latteEngine,
		OptionsProvider $optionsProvider,
		ReturnRouter $returnRouter,
		AssetManager $assetManager
	) {
		$this->wpAdapter       = $wpAdapter;
		$this->latteEngine     = $latteEngine;
		$this->optionsProvider = $optionsProvider;
		$this->returnRouter    = $returnRouter;
		$this->assetManager    = $assetManager;
	}

	public function register(): void {
		$this->wpAdapter->addShortcode( 'packeta_return_form', [ $this, 'render' ] );
	}

	public function render(): string {
		if ( ! $this->optionsProvider->isReturnsEnabled() || ! $this->optionsProvider->isGuestReturnsAllowed() ) {
			return '';
		}

		$this->assetManager->enqueueScript( 'packetery-front-return', 'public/js/front-return.js', true );
		$this->wpAdapter->localizeScript(
			'packetery-front-return',
			'packeteryReturnFormSettings',
			[
				'createUrl'    => $this->returnRouter->getCreateGuestUrl(),
				'nonce'        => (string) $this->wpAdapter->createNonce( 'wp_rest' ),
				'translations' => [
					'sending' => $this->wpAdapter->__( 'Sending…', 'packeta' ),
					'created' => $this->wpAdapter->__( 'Return created:', 'packeta' ),
					'error'   => $this->wpAdapter->__( 'The return could not be created. Please try again later.', 'packeta' ),
				],
			]
		);

		return $this->latteEngine->renderToString(
			PACKETERY_PLUGIN_DIR . '/template/order/return-form-guest.latte',
			[
				'translations' => [
					'orderNumberLabel' => $this->wpAdapter->__( 'Order number', 'packeta' ),
					'emailLabel'       => $this->wpAdapter->__( 'Billing e-mail', 'packeta' ),
					'phoneLabel'       => $this->wpAdapter->__( 'Contact phone (optional)', 'packeta' ),
					'submit'           => $this->wpAdapter->__( 'Send the return', 'packeta' ),
				],
			]
		);
	}
}
