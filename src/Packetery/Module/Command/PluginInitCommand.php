<?php

declare(strict_types=1);

namespace Packetery\Module\Command;

use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options;
use Packetery\Nette\Forms\Container;

class PluginInitCommand {
	const NAME = 'packeta-plugin-init';

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var Options\Page
	 */
	private $optionsPage;

	public function __construct(
		WpAdapter $wpAdapter,
		Options\Page $optionsPage
	) {
		$this->wpAdapter   = $wpAdapter;
		$this->optionsPage = $optionsPage;
	}

	/**
	 * Inits packeta plugin.
	 *
	 * ## OPTIONS
	 *
	 * <api-password>
	 * : API password.
	 *
	 * <sender>
	 * : API sender.
	 *
	 * @when after_wp_load
	 */
	public function __invoke( array $args ): void {
		[ $apiPassword, $sender ] = $args;

		$values = [
			'api_password'        => $apiPassword,
			'sender'              => $sender,
			'cod_payment_methods' => [ 'cod' ],
		];

		$form = $this->optionsPage->create_form();
		/** @var Container $container */
		$container = $form->getComponent( Options\OptionNames::PACKETERY );
		$container->setValues( $values );

		if ( ! $form->isValid() ) {
			$this->wpAdapter->cliError( implode( PHP_EOL, $form->getErrors() ) );
		}

		$values = $container->getValues( 'array' );
		$this->wpAdapter->updateOption( Options\OptionNames::PACKETERY, $values );

		$this->wpAdapter->cliSuccess( 'General plugin options updated' );
	}
}
