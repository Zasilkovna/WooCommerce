<?php

namespace Packetery\Module\Forms;

use Packetery\Module\FormFactory;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Options\Page;
use Packetery\Module\Order\CurrencyConversion;
use Packetery\Nette\Forms\Form;

class CurrencyRatesFormFactory {

	private WpAdapter $wpAdapter;
	private WcAdapter $wcAdapter;
	private FormFactory $formFactory;
	private OptionsProvider $optionsProvider;
	private CurrencyConversion $currencyConversion;

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		FormFactory $formFactory,
		OptionsProvider $optionsProvider,
		CurrencyConversion $currencyConversion
	) {
		$this->formFactory        = $formFactory;
		$this->optionsProvider    = $optionsProvider;
		$this->currencyConversion = $currencyConversion;
		$this->wpAdapter          = $wpAdapter;
		$this->wcAdapter          = $wcAdapter;
	}

	/**
	 * @param array{Page, string} $onSuccessCallback
	 */
	public function createForm( array $onSuccessCallback ): Form {
		$form  = $this->formFactory->create( 'packetery_currency_rates_form' );
		$rates = $this->optionsProvider->getCustomCurrencyRates();

		$form
			->addCheckbox( 'enabled', $this->wpAdapter->__( 'Enable custom currency rates', 'packeta' ) )
			->setDefaultValue( $this->optionsProvider->isCustomCurrencyRatesEnabled() );

		$ratesContainer = $form->addContainer( 'rates' );
		foreach ( $this->currencyConversion->getList() as $code ) {
			if ( $code === $this->wcAdapter->getWoocommerceCurrency() ) {
				continue;
			}
			$ratesContainer
				->addText( $code, "1 $code =" )
				->setRequired( false )
				->addRule( Form::FLOAT )
				->setDefaultValue( $rates[ $code ] ?? '' );
		}

		$form->addSubmit( 'saveCurrencyRates', $this->wpAdapter->__( 'Save changes', 'packeta' ) );
		$form->onSuccess[] = $onSuccessCallback;

		return $form;
	}
}
