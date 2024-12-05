<?php
/**
 * Class FeatureFlagNotice
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Options\FlagManager;

use Packetery\Latte\Engine;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Plugin;

/**
 * Class FeatureFlagNotice
 *
 * @package Packetery
 */
class FeatureFlagNotice {

	public const ACTION_HIDE_SPLIT_MESSAGE = 'dismiss_split_message';

	/**
	 * Latte engine.
	 *
	 * @var Engine
	 */
	private $latteEngine;

	/**
	 * ModuleHelper.
	 *
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	/**
	 * WP adapter.
	 *
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * Constructor.
	 *
	 * @param Engine       $latteEngine  Latte engine.
	 * @param ModuleHelper $moduleHelper ModuleHelper.
	 * @param WpAdapter    $wpAdapter    WP adapter.
	 */
	public function __construct(
		Engine $latteEngine,
		ModuleHelper $moduleHelper,
		WpAdapter $wpAdapter
	) {
		$this->latteEngine  = $latteEngine;
		$this->moduleHelper = $moduleHelper;
		$this->wpAdapter    = $wpAdapter;
	}

	/**
	 * Print split activation notice.
	 *
	 * @return void
	 */
	public function renderSplitActivationNotice(): void {
		$dismissUrl = $this->wpAdapter->addQueryArg( [ Plugin::PARAM_PACKETERY_ACTION => self::ACTION_HIDE_SPLIT_MESSAGE ] );
		$this->latteEngine->render(
			PACKETERY_PLUGIN_DIR . '/template/admin-notice.latte',
			[
				'message' => [
					'type'    => 'warning',
					'escape'  => false,
					'message' => sprintf(
					// translators: 1: documentation link start 2: link end 3: dismiss link start 4: link end.
						__(
							'We have just enabled new options for setting Packeta pickup points. You can now choose a different price for Z-Box and pickup points in the carrier settings. More information can be found in %1$sthe plugin documentation%2$s. %3$sDismiss this message%4$s',
							'packeta'
						),
						...$this->moduleHelper->createLinkParts( 'https://github.com/Zasilkovna/WooCommerce/wiki', '_blank' ),
						...$this->moduleHelper->createLinkParts( $dismissUrl, null, 'button button-primary' )
					),
				],
			]
		);
	}

}
