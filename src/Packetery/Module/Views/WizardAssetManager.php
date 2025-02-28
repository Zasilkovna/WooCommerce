<?php

namespace Packetery\Module\Views;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\Page;
use Packetery\Nette\Http\Request;

class WizardAssetManager {
	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var AssetManager
	 */
	private $assetManager;

	/**
	 * @var Request
	 */
	private $request;

	public function __construct(
		AssetManager $assetManager,
		Request $request,
		WpAdapter $wpAdapter
	) {
		$this->assetManager = $assetManager;
		$this->request      = $request;
		$this->wpAdapter    = $wpAdapter;
	}

	public function enqueueWizardAssets(): void {
		$page                       = $this->request->getQuery( 'page' );
		$isWizardEnabled            = $this->request->getQuery( 'wizard-enabled' ) === 'true';
		$isWizardExampleTourEnabled = $this->request->getQuery( 'wizard-example-tour-enabled' ) === 'true';

		if ( $page === Page::SLUG && $isWizardEnabled ) {
			$this->assetManager->enqueueStyle( 'packetery-driverjs-css', 'public/libs/driverjs-1.3.4/driver.css' );
			$this->assetManager->enqueueScript( 'packetery-driverjs', 'public/libs/driverjs-1.3.4/driver.js.iife.js', true );

			$adminWizardTourSettings = [
				'translations' => [
					'next'        => $this->wpAdapter->__( 'Next', 'packeta' ),
					'previous'    => $this->wpAdapter->__( 'Previous', 'packeta' ),
					'close'       => $this->wpAdapter->__( 'Close', 'packeta' ),
					'of'          => $this->wpAdapter->__( 'of', 'packeta' ),
					'apiPassword' => [
						'title'       => $this->wpAdapter->__( 'API password', 'packeta' ),
						'description' => sprintf(
							$this->wpAdapter->__( 'API password can be found at %s', 'packeta' ),
							'<a href="https://client.packeta.com/support" target="_blank">https://client.packeta.com/support<a/>'
						),
					],
					'apiSender'   => [
						'title'       => $this->wpAdapter->__( 'Sender', 'packeta' ),
						'description' => sprintf(
						/* translators: 1: emphasis start 2: emphasis end 3: client section link start 4: client section link end */
							esc_html__( 'Fill here %1$ssender label%2$s - you will find it in %3$sclient section%4$s - user information - field \'Indication\'.', 'packeta' ),
							'<strong>',
							'</strong>',
							'<a href="https://client.packeta.com/senders" target="_blank">',
							'</a>'
						),
					],
				],
			];

			if ( $isWizardExampleTourEnabled ) {
				$this->assetManager->enqueueScript(
					'packetery-admin-wizard-tour',
					'public/js/tours/admin-wizard-example.js',
					true,
					[
						'packetery-driverjs',
					]
				);

				$this->wpAdapter->localizeScript( 'packetery-admin-wizard-tour', 'adminWizardTourSettings', $adminWizardTourSettings );
			}
		}
	}
}
