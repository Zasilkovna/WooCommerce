<?php

declare( strict_types=1 );

namespace Packetery\Module\Carrier;

use Packetery\Module\Forms\CarrierFormFactory;
use Packetery\Module\Forms\ShippingClassFormFactory;
use Packetery\Module\Forms\ShippingFormHelper;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Views\UrlBuilder;
use Packetery\Nette\Http\Request;

class ShippingClassPage {

	public const PARAMETER_CLASS_ID = 'class_id';

	private Request $httpRequest;
	private UrlBuilder $urlBuilder;
	private WpAdapter $wpAdapter;
	private ShippingClassFormFactory $shippingClassFormFactory;
	private CarrierFormFactory $carrierFormFactory;
	private ShippingFormHelper $shippingFormHelper;

	public function __construct(
		Request $httpRequest,
		UrlBuilder $urlBuilder,
		WpAdapter $wpAdapter,
		ShippingClassFormFactory $shippingClassFormFactory,
		CarrierFormFactory $carrierFormFactory,
		ShippingFormHelper $shippingFormHelper
	) {
		$this->httpRequest              = $httpRequest;
		$this->urlBuilder               = $urlBuilder;
		$this->wpAdapter                = $wpAdapter;
		$this->shippingClassFormFactory = $shippingClassFormFactory;
		$this->carrierFormFactory       = $carrierFormFactory;
		$this->shippingFormHelper       = $shippingFormHelper;
	}

	/**
	 * @param string $carrierId
	 *
	 * @return array<string, mixed>
	 */
	public function getTemplateParams( string $carrierId ): array {
		$activeTab = $this->httpRequest->getQuery( self::PARAMETER_CLASS_ID );
		$classTabs = [];
		foreach ( $this->shippingFormHelper->getShippingClasses() as $shippingClass ) {
			$form = null;
			if ( $activeTab === $shippingClass['slug'] ) {
				$form = $this->shippingClassFormFactory->createFromClassAndCarrier( $shippingClass, $carrierId );
			}

			$classTabs[] = [
				'title'           => $shippingClass['name'],
				'slug'            => $shippingClass['slug'],
				'link'            => $this->urlBuilder->getCarrierConfigLink( $carrierId, (string) $shippingClass['slug'] ),
				'form'            => $form,
				'weightSectionId' => ( $form !== null ? $this->shippingFormHelper->createFieldContainerId( $form, OptionsPage::FORM_FIELD_WEIGHT_LIMITS ) : '' ),
				'valueSectionId'  => ( $form !== null ? $this->shippingFormHelper->createFieldContainerId( $form, OptionsPage::FORM_FIELD_PRODUCT_VALUE_LIMITS ) : '' ),
			];
		}

		$tabbedTemplateParams = [
			'shippingClasses'   => $this->shippingFormHelper->getShippingClasses(),
			'generalTabLink'    => $this->urlBuilder->getCarrierConfigLink( $carrierId ),
			'activeTab'         => $activeTab,
			'tabs'              => $classTabs,
			'formTemplate'      => $this->carrierFormFactory->createFormTemplate( $carrierId ),
			'replicationFormId' => 'frm-' . OptionPrefixer::getOptionId( $carrierId ),
			'translations'      => [
				'general' => $this->wpAdapter->__( 'General', 'packeta' ),
			],
		];

		return $tabbedTemplateParams;
	}
}
