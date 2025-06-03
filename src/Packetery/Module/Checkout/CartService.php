<?php

declare( strict_types=1 );

namespace Packetery\Module\Checkout;

use Packetery\Module\Carrier;
use Packetery\Module\Exception\ProductNotFoundException;
use Packetery\Module\Framework\WcAdapter;
use Packetery\Module\Framework\WpAdapter;
use Packetery\Module\Options\OptionsProvider;
use Packetery\Module\Product\ProductEntityFactory;
use Packetery\Module\ProductCategory\ProductCategoryEntityFactory;
use WC_Product;

class CartService {

	private const CRITERIA_BY_LENGTH = 1;
	public const CRITERIA_BY_SUM     = 2;

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
	 * @throws ProductNotFoundException
	 */
	public function isAgeVerificationRequired(): bool {
		if ( $this->wpAdapter->didAction( 'wp_loaded' ) === 0 ) {
			return false;
		}

		$products = $this->wcAdapter->cartGetCartContent();

		foreach ( $products as $product ) {
			$productEntity = $this->productEntityFactory->fromPostId( $product['product_id'] );
			if ( $productEntity->isPhysical() && $productEntity->isAgeVerificationRequired() ) {
				return true;
			}
		}

		return false;
	}

	public function getCartWeightKg(): float {
		if ( $this->wpAdapter->didAction( 'wp_loaded' ) === 0 ) {
			return 0.0;
		}

		$weight   = $this->wcAdapter->cartGetCartContentsWeight();
		$weightKg = $this->wcAdapter->getWeight( $weight, 'kg' );

		if ( $weightKg !== 0.0 ) {
			$weightKg += $this->optionsProvider->getPackagingWeight();
		}

		return $weightKg;
	}

	public function getTotalCartProductValue(): float {
		$totalProductPrice = 0.0;

		foreach ( $this->wcAdapter->cartGetCartContent() as $cartItem ) {
			$totalProductPrice += (float) $cartItem['data']->get_price( 'raw' ) * (float) $cartItem['quantity'];
		}

		return $totalProductPrice;
	}

	/**
	 * @throws ProductNotFoundException
	 */
	public function getDisallowedShippingRateIds(): array {
		$cartProducts = $this->wcAdapter->cartGetCartContent();

		$disallowedShippingRateIds = [];
		foreach ( $cartProducts as $cartProduct ) {
			$productEntity = $this->productEntityFactory->fromPostId( $cartProduct['product_id'] );

			if ( $productEntity->isPhysical() === false ) {
				continue;
			}

			$disallowedShippingRateIds[] = $productEntity->getDisallowedShippingRateIds();
		}

		return array_unique( array_merge( [], ...$disallowedShippingRateIds ) );
	}

	/**
	 * Returns tax_class with the highest tax_rate of cart products, false if no product is taxable.
	 *
	 * @return string|null
	 * @throws ProductNotFoundException
	 */
	public function getTaxClassWithMaxRate(): ?string {
		$products   = $this->wcAdapter->cartGetCartContent();
		$taxClasses = [];

		foreach ( $products as $cartProduct ) {
			$product = $this->wcAdapter->productFactoryGetProduct( $cartProduct['product_id'] );
			if ( ! ( $product instanceof WC_Product ) ) {
				throw new ProductNotFoundException( "Product {$cartProduct['product_id']} not found." );
			}
			if ( $product->is_taxable() && is_string( $product->get_tax_class() ) ) {
				$taxClasses[] = $product->get_tax_class();
			}
		}

		if ( count( $taxClasses ) === 0 ) {
			return null;
		}

		$taxClasses = array_unique( $taxClasses );
		if ( count( $taxClasses ) === 1 ) {
			return $taxClasses[0];
		}

		$taxRates = [];
		$customer = $this->wcAdapter->cartGetCustomer();
		foreach ( $taxClasses as $taxClass ) {
			$taxRates[ $taxClass ] = $this->wcAdapter->taxGetRates( $taxClass, $customer );
		}

		$maxRate        = 0;
		$resultTaxClass = null;
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

	public function getCartContentsTotalIncludingTax(): float {
		return $this->wcAdapter->cartGetCartContentsTotal() + $this->wcAdapter->cartGetCartContentsTax();
	}

	/**
	 * @throws ProductNotFoundException
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

	public function getBiggestProductSize( int $mode = self::CRITERIA_BY_LENGTH ): ?array {
		if ( $this->wpAdapter->didAction( 'wp_loaded' ) === 0 ) {
			return null;
		}

		$products = $this->wcAdapter->cartGetCartContent();
		$maxSizes = [
			'length' => 0,
			'width'  => 0,
			'depth'  => 0,
		];

		foreach ( $products as $product ) {
			$productEntity = $this->productEntityFactory->fromPostId( $product['product_id'] );
			if ( $productEntity->isPhysical() && (
					$productEntity->getLengthInCm( $this->wpAdapter ) > 0 ||
					$productEntity->getWidthInCm( $this->wpAdapter ) > 0 ||
					$productEntity->getHeightInCm( $this->wpAdapter ) > 0
				)
			) {
				if ( $mode === self::CRITERIA_BY_SUM ) {
					$productSizeSum =
						$productEntity->getLengthInCm( $this->wpAdapter ) +
						$productEntity->getWidthInCm( $this->wpAdapter ) +
						$productEntity->getHeightInCm( $this->wpAdapter );
					if ( $productSizeSum > array_sum( $maxSizes ) ) {
						$maxSizes = [
							'length' => $productEntity->getLengthInCm( $this->wpAdapter ),
							'width'  => $productEntity->getWidthInCm( $this->wpAdapter ),
							'depth'  => $productEntity->getHeightInCm( $this->wpAdapter ),
						];
					}
				} else {
					$productSizes = [
						$productEntity->getLengthInCm( $this->wpAdapter ),
						$productEntity->getWidthInCm( $this->wpAdapter ),
						$productEntity->getHeightInCm( $this->wpAdapter ),
					];
					rsort( $productSizes, SORT_NUMERIC );
					if ( $productSizes[0] > $maxSizes['length'] ) {
						$maxSizes = [
							'length' => $productSizes[0],
							'width'  => $productSizes[1],
							'depth'  => $productSizes[2],
						];
					}
				}
			}
		}

		if ( $maxSizes['length'] === 0 ) {
			return null;
		}

		return $maxSizes;
	}

	public function cartContainsProductOversizedForCarrier( Carrier\Options $carrierOptions ): bool {
		$sizeRestrictions = $carrierOptions->getSizeRestrictions();
		if ( $sizeRestrictions === null ) {
			return false;
		}
		$biggestProductSizeBySum    = $this->getBiggestProductSize( self::CRITERIA_BY_SUM );
		$biggestProductSizeByLength = $this->getBiggestProductSize();
		if ( $biggestProductSizeBySum === null || $biggestProductSizeByLength === null ) {
			return false;
		}

		if ( isset( $sizeRestrictions['maximum_length'] ) && is_numeric( trim( (string) $sizeRestrictions['maximum_length'] ) ) ) {
			$productMax = max( $biggestProductSizeByLength );
			if ( $productMax > $sizeRestrictions['maximum_length'] ) {
				return true;
			}
		}
		if ( isset( $sizeRestrictions['dimensions_sum'] ) && is_numeric( trim( (string) $sizeRestrictions['dimensions_sum'] ) ) ) {
			$productSum = array_sum( $biggestProductSizeBySum );
			if ( $productSum > $sizeRestrictions['dimensions_sum'] ) {
				return true;
			}
		}

		if (
			isset( $sizeRestrictions['length'], $sizeRestrictions['width'], $sizeRestrictions['height'] )
			&& is_numeric( trim( (string) $sizeRestrictions['length'] ) )
			&& is_numeric( trim( (string) $sizeRestrictions['width'] ) )
			&& is_numeric( trim( (string) $sizeRestrictions['height'] ) )
		) {
			$dimensions = [
				$sizeRestrictions['length'],
				$sizeRestrictions['width'],
				$sizeRestrictions['height'],
			];
			rsort( $dimensions, SORT_NUMERIC );
			rsort( $biggestProductSizeByLength, SORT_NUMERIC );

			foreach ( $dimensions as $index => $dimension ) {
				if ( $biggestProductSizeByLength[ $index ] > $dimension ) {
					return true;
				}
			}
		}

		return false;
	}
}
