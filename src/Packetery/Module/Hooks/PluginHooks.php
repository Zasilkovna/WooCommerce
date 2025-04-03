<?php

namespace Packetery\Module\Hooks;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\ModuleHelper;
use Packetery\Module\Options;
use Packetery\Module\Options\FlagManager\FeatureFlagNotice;
use Packetery\Module\Options\FlagManager\FeatureFlagProvider;
use Packetery\Module\Order;
use Packetery\Module\Plugin;
use Packetery\Nette\Http\Request;

class PluginHooks {

	/**
	 * @var Request
	 */
	private $request;

	/**
	 * @var FeatureFlagProvider
	 */
	private $featureFlagProvider;

	/**
	 * @var Order\PacketSubmitter
	 */
	private $packetSubmitter;

	/**
	 * @var Order\PacketCanceller
	 */
	private $packetCanceller;

	/**
	 * @var Order\PacketClaimSubmitter
	 */
	private $packetClaimSubmitter;

	/**
	 * @var ModuleHelper
	 */
	private $moduleHelper;

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	public function __construct(
		Request $request,
		FeatureFlagProvider $featureFlagProvider,
		Order\PacketSubmitter $packetSubmitter,
		Order\PacketCanceller $packetCanceller,
		Order\PacketClaimSubmitter $packetClaimSubmitter,
		ModuleHelper $moduleHelper,
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter
	) {

		$this->request              = $request;
		$this->featureFlagProvider  = $featureFlagProvider;
		$this->packetSubmitter      = $packetSubmitter;
		$this->packetCanceller      = $packetCanceller;
		$this->packetClaimSubmitter = $packetClaimSubmitter;
		$this->moduleHelper         = $moduleHelper;
		$this->wpAdapter            = $wpAdapter;
		$this->wcAdapter            = $wcAdapter;
	}

	/**
	 * Declares plugin compability with features.
	 *
	 * @return void
	 */
	public function declareWooCommerceCompability(): void {
		if ( class_exists( FeaturesUtil::class ) === false ) {
			return;
		}

		// High-Performance Order Storage.
		$this->wcAdapter->featuresUtilDeclareCompatibility( 'custom_order_tables', ModuleHelper::getPluginMainFilePath() );
	}

	/**
	 * Loads plugin translation file by user locale.
	 */
	public function loadTranslation(): void {
		$domain = Plugin::DOMAIN;
		$this->wpAdapter->unloadTextDomain( $domain, true );
		$locale = $this->moduleHelper->getLocale();
		$moFile = WP_LANG_DIR . "/plugins/$domain-$locale.mo";

		if ( file_exists( $moFile ) ) {
			$this->wpAdapter->loadPluginTextDomain( $domain );
		} else {
			$this->wpAdapter->loadDefaultTextDomain();
		}
	}

	/**
	 * Adds action links visible at the plugin screen.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
	 *
	 * @param array $links Plugin Action links.
	 *
	 * @return array
	 */
	public function addPluginActionLinks( array $links ): array {
		$settingsLink = '<a href="' . $this->wpAdapter->escUrl( $this->wpAdapter->adminUrl( 'admin.php?page=' . Options\Page::SLUG ) ) . '" aria-label="' .
			$this->wpAdapter->escAttr( $this->wpAdapter->__( 'View the plugin documentation', 'packeta' ) ) . '">' .
			$this->wpAdapter->escHtml( $this->wpAdapter->__( 'Settings', 'packeta' ) ) . '</a>';

		array_unshift( $links, $settingsLink );

		return $links;
	}

	/**
	 * Adds links to the plugin list screen.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/plugin_row_meta/
	 *
	 * @param array  $links Plugin Row Meta.
	 * @param string $pluginFileName Plugin Base file.
	 *
	 * @return array
	 */
	public function addPluginRowMeta( array $links, string $pluginFileName ): array {
		if ( strpos( $pluginFileName, basename( ModuleHelper::getPluginMainFilePath() ) ) === false ) {
			return $links;
		}
		$links[] = '<a href="' . $this->wpAdapter->escUrl( 'https://github.com/Zasilkovna/WooCommerce/wiki' ) . '" aria-label="' .
			$this->wpAdapter->escAttr( $this->wpAdapter->__( 'View Packeta documentation', 'packeta' ) ) . '">' .
			$this->wpAdapter->escHtml( $this->wpAdapter->__( 'Documentation', 'packeta' ) ) . '</a>';

		return $links;
	}

	/**
	 * Check for action parameter and process wanted action.
	 *
	 * @return void
	 */
	public function handleActions(): void {
		$action = $this->request->getQuery( Plugin::PARAM_PACKETERY_ACTION );

		if ( $action === Order\PacketActionsCommonLogic::ACTION_SUBMIT_PACKET ) {
			$this->packetSubmitter->processAction();
		}

		if ( $action === Order\PacketActionsCommonLogic::ACTION_SUBMIT_PACKET_CLAIM ) {
			$this->packetClaimSubmitter->processAction();
		}

		if ( $action === Order\PacketActionsCommonLogic::ACTION_CANCEL_PACKET ) {
			$this->packetCanceller->processAction();
		}

		if ( $action === FeatureFlagNotice::ACTION_HIDE_SPLIT_MESSAGE ) {
			$this->featureFlagProvider->dismissSplitActivationNotice();
		}
	}
}
