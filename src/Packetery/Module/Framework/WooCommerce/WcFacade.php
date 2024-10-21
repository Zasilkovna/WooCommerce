<?php

namespace Packetery\Module\Framework\WooCommerce;



use Packetery\Module\Framework\WcCartAdapter;
use Packetery\Module\Framework\WcCustomerAdapter;
use Packetery\Module\Framework\WcPageAdapter;
use Packetery\Module\Framework\WcTaxAdapter;

//Basically facade
class WcFacade
{
	/**
	 * @var WcTaxAdapter
	 */
	public $wcTax;

	/**
	 * @var WcPageAdapter
	 */
	public $wcPage;
	public $wcCart;
	public $wcCustomer;

	public function __construct(
		WcCartAdapter $wcCart,
		WcTaxAdapter $wcTax,
		WcPageAdapter $wcPage,
		WcCustomerAdapter $wcCustomer,
	) {
		$this->wcTax      = $wcTax;
		$this->wcPage = $wcPage;
		$this->wcCart = $wcCart;
		$this->wcCustomer = $wcCustomer;
	}

	//Some getter might be here
}
