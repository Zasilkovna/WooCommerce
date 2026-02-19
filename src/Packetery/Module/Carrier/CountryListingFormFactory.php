<?php

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Module\FormFactory;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Nette\Forms\Form;
use Packetery\Nette\Http\Request;

class CountryListingFormFactory {

	private FormFactory $formFactory;
	private Request $httpRequest;
	private WpAdapter $wpAdapter;

	public function __construct( FormFactory $formFactory, Request $httpRequest, WpAdapter $wpAdapter ) {
		$this->formFactory = $formFactory;
		$this->httpRequest = $httpRequest;
		$this->wpAdapter   = $wpAdapter;
	}

	/**
	 * @param array<string, string> $countryChoices
	 */
	public function create( array $countryChoices ): Form {
		$form = $this->formFactory->create();
		$form->setAction( $this->wpAdapter->adminUrl( 'admin.php?page=' . OptionsPage::SLUG ) ?? '' );
		$form->setMethod( Form::GET );
		$carrierFilterInput = $form->addText( CountryListingPage::PARAM_CARRIER_FILTER );
		$carrierFilterInput->addCondition( Form::FILLED )
			->addRule( Form::MIN_LENGTH, $this->wpAdapter->__( 'Enter at least 2 characters to search.', 'packeta' ), 2 );
		$countrySelect = $form->addMultiSelect( CountryListingPage::PARAM_COUNTRY_FILTER, $this->wpAdapter->__( 'Country', 'packeta' ), $countryChoices );
		$countrySelect->setHtmlAttribute( 'data-packetery-select2', 'true' );
		$countrySelect->setHtmlAttribute( 'class', 'packetery-country-filter-select' );
		$form->addCheckbox( CountryListingPage::PARAM_ACTIVE_ONLY, $this->wpAdapter->__( 'Show only active carriers', 'packeta' ) )
			->setDefaultValue( false );
		$form->addSubmit( 'filter', $this->wpAdapter->__( 'Filter', 'packeta' ) );

		$carrierFilterFromQuery = $this->httpRequest->getQuery( CountryListingPage::PARAM_CARRIER_FILTER );
		$form->setDefaults(
			[
				CountryListingPage::PARAM_CARRIER_FILTER => is_string( $carrierFilterFromQuery ) ? $carrierFilterFromQuery : '',
				CountryListingPage::PARAM_COUNTRY_FILTER => $this->httpRequest->getQuery( CountryListingPage::PARAM_COUNTRY_FILTER ) ?? [],
				CountryListingPage::PARAM_ACTIVE_ONLY    => (bool) $this->httpRequest->getQuery( CountryListingPage::PARAM_ACTIVE_ONLY ),
			]
		);

		return $form;
	}
}
