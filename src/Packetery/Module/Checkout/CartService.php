<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Module\Exception\ProductNotFoundException;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Product\ProductEntityFactory;
use Packetery\Module\ProductCategory\ProductCategoryEntityFactory;
use WC_Product;

class CartService {

	/**
	 * @var WpAdapter
	 */
	private $wpAdapter;

	/**
	 * @var WcAdapter
	 */
	private $wcAdapter;

	/**
	 * @var ProductEntityFactory
	 */
	private $productEntityFactory;

	/**
	 * @var ProductCategoryEntityFactory
	 */
	private $productCategoryEntityFactory;

	/**
	 * @var OptionsProvider
	 */
	private $optionsProvider;

	public function __construct(
		WpAdapter $wpAdapter,
		WcAdapter $wcAdapter,
		ProductEntityFactory $productEntityFactory,
		ProductCategoryEntityFactory $productCategoryEntityFactory,
		OptionsProvider $optionsProvider
	) {
		$this->wpAdapter                    = $wpAdapter;
		$this->wcAdapter                    = $wcAdapter;
		$this->productEntityFactory         = $productEntityFactory;
		$this->productCategoryEntityFactory = $productCategoryEntityFactory;
		$this->optionsProvider              = $optionsProvider;
	}

	/**
	 * Tells if age verification is required by products in cart.
	 *
	 * @return bool
	 * @throws ProductNotFoundException Product not found.
	 */
	public function isAgeVerification18PlusRequired(): bool {
		if ( $this->wpAdapter->didAction( 'wp_loaded' ) === 0 ) {
			return false;
		}

		$products = $this->wcAdapter->cartGetCartContent();

		foreach ( $products as $product ) {
			$productEntity = $this->productEntityFactory->fromPostId( $product['product_id'] );
			if ( $productEntity->isPhysical() && $productEntity->isAgeVerification18PlusRequired() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets cart contents weight in kg.
	 *
	 * @return float
	 */
	public function getCartWeightKg(): float {
		if ( $this->wpAdapter->didAction( 'wp_loaded' ) === 0 ) {
			return 0.0;
		}

		$weight   = $this->wcAdapter->cartGetCartContentsWeight();
		$weightKg = $this->wcAdapter->getWeight( $weight, 'kg' );

		if ( 0.0 !== $weightKg ) {
			$weightKg += $this->optionsProvider->getPackagingWeight();
		}

		return $weightKg;
	}

	/**
	 * Gets total cart product value.
	 *
	 * @return float
	 */
	public function getTotalCartProductValue(): float {
		$totalProductPrice = 0.0;

		foreach ( $this->wcAdapter->cartGetCartContent() as $cartItem ) {
			$totalProductPrice += (float) $cartItem['data']->get_price( 'raw' ) * $cartItem['quantity'];
		}

		return $totalProductPrice;
	}

	/**
	 * Gets disallowed shipping rate ids.
	 *
	 * @return array
	 * @throws ProductNotFoundException Product not found.
	 */
	public function getDisallowedShippingRateIds(): array {
		$cartProducts = $this->wcAdapter->cartGetCartContent();

		$arraysToMerge = [];
		foreach ( $cartProducts as $cartProduct ) {
			$productEntity = $this->productEntityFactory->fromPostId( $cartProduct['product_id'] );

			if ( false === $productEntity->isPhysical() ) {
				continue;
			}

			$arraysToMerge[] = $productEntity->getDisallowedShippingRateIds();
		}

		return array_unique( array_merge( [], ...$arraysToMerge ) );
	}

	/**
	 * Returns tax_class with the highest tax_rate of cart products, false if no product is taxable.
	 *
	 * @return false|string
	 * @throws ProductNotFoundException Product not found.
	 */
	public function getTaxClassWithMaxRate() {
		$products   = $this->wcAdapter->cartGetCartContent();
		$taxClasses = [];

		foreach ( $products as $cartProduct ) {
			$product = $this->wcAdapter->productFactoryGetProduct( $cartProduct['product_id'] );
			if ( ! ( $product instanceof WC_Product ) ) {
				throw new ProductNotFoundException( "Product {$cartProduct['product_id']} not found." );
			}
			if ( $product->is_taxable() ) {
				$taxClasses[] = $product->get_tax_class();
			}
		}

		if ( count( $taxClasses ) === 0 ) {
			return false;
		}

		$taxClasses = array_unique( $taxClasses );
		if ( 1 === count( $taxClasses ) ) {
			return $taxClasses[0];
		}

		$taxRates = [];
		$customer = $this->wcAdapter->cartGetCustomer();
		foreach ( $taxClasses as $taxClass ) {
			$taxRates[ $taxClass ] = $this->wcAdapter->taxGetRates( $taxClass, $customer );
		}

		$maxRate        = 0;
		$resultTaxClass = false;
		foreach ( $taxRates as $taxClassName => $taxClassRates ) {
			foreach ( $taxClassRates as $rate ) {
				if ( $rate['rate'] > $maxRate ) {
					$maxRate        = $rate['rate'];
					$resultTaxClass = $taxClassName;
				}
			}
		}

		return $resultTaxClass;
	}

	/**
	 * Tells cart contents total price including tax and discounts.
	 *
	 * @return float
	 */
	public function getCartContentsTotalIncludingTax(): float {
		return $this->wcAdapter->cartGetCartContentsTotal() + $this->wcAdapter->cartGetCartContentsTax();
	}

	/**
	 * Check if given carrier is disabled in products categories in cart
	 *
	 * @param string $shippingRate Shipping rate.
	 * @param array  $cartProducts Array of cart products.
	 *
	 * @return bool
	 * @throws ProductNotFoundException Product not found.
	 */
	public function isShippingRateRestrictedByProductsCategory( string $shippingRate, array $cartProducts ): bool {
		if ( count( $cartProducts ) === 0 ) {
			return false;
		}

		foreach ( $cartProducts as $cartProduct ) {
			if ( ! isset( $cartProduct['product_id'] ) ) {
				continue;
			}
			$product = $this->wcAdapter->productFactoryGetProduct( $cartProduct['product_id'] );
			if ( ! ( $product instanceof WC_Product ) ) {
				throw new ProductNotFoundException( "Product {$cartProduct['product_id']} not found." );
			}
			$productCategoryIds = $product->get_category_ids();

			foreach ( $productCategoryIds as $productCategoryId ) {
				$productCategoryEntity           = $this->productCategoryEntityFactory->fromTermId( (int) $productCategoryId );
				$disallowedCategoryShippingRates = $productCategoryEntity->getDisallowedShippingRateIds();
				if ( in_array( $shippingRate, $disallowedCategoryShippingRates, true ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
