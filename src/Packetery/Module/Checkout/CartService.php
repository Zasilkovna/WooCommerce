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

	public const SIZE_RESTRICTION_MAXIMUM_LENGTH = 'maximum_length';
	public const SIZE_RESTRICTION_DIMENSIONS_SUM = 'dimensions_sum';
	public const SIZE_RESTRICTION_DIMENSIONS     = 'dimensions';

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
	 * @return int|null Id of the first cart product that disallows the rate.
	 * @throws ProductNotFoundException
	 */
	public function findProductDisallowingRate( string $shippingRate ): ?int {
		foreach ( $this->wcAdapter->cartGetCartContent() as $cartProduct ) {
			$productEntity = $this->productEntityFactory->fromPostId( $cartProduct['product_id'] );

			if ( $productEntity->isPhysical() === false ) {
				continue;
			}

			if ( in_array( $shippingRate, $productEntity->getDisallowedShippingRateIds(), true ) ) {
				return (int) $cartProduct['product_id'];
			}
		}

		return null;
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
		return $this->findCategoryRestrictingRate( $shippingRate, $cartProducts ) !== null;
	}

	/**
	 * @param string                   $shippingRate Carrier option id.
	 * @param array<int|string, mixed> $cartProducts Cart contents as WooCommerce hands them over.
	 *
	 * @return array{productId: int, categoryId: int}|null
	 * @throws ProductNotFoundException
	 */
	public function findCategoryRestrictingRate( string $shippingRate, array $cartProducts ): ?array {
		foreach ( $cartProducts as $cartProduct ) {
			$productId = is_array( $cartProduct ) ? ( $cartProduct['product_id'] ?? null ) : null;
			if ( is_numeric( $productId ) === false ) {
				continue;
			}
			$productId = (int) $productId;
			$product   = $this->wcAdapter->productFactoryGetProduct( $productId );
			if ( ! ( $product instanceof WC_Product ) ) {
				throw new ProductNotFoundException( "Product {$productId} not found." );
			}

			foreach ( $product->get_category_ids() as $productCategoryId ) {
				$productCategoryEntity = $this->productCategoryEntityFactory->fromTermId( (int) $productCategoryId );
				if ( in_array( $shippingRate, $productCategoryEntity->getDisallowedShippingRateIds(), true ) ) {
					return [
						'productId'  => $productId,
						'categoryId' => (int) $productCategoryId,
					];
				}
			}
		}

		return null;
	}

	public function getBiggestProductSize( int $mode = self::CRITERIA_BY_LENGTH ): ?array {
		$biggestProduct = $this->findBiggestProduct( $mode );

		return $biggestProduct === null ? null : $biggestProduct['sizes'];
	}

	/**
	 * @return array{productId: int, sizes: array{length: float|int, width: float|int, depth: float|int}}|null
	 * @throws ProductNotFoundException
	 */
	private function findBiggestProduct( int $mode = self::CRITERIA_BY_LENGTH ): ?array {
		if ( $this->wpAdapter->didAction( 'wp_loaded' ) === 0 ) {
			return null;
		}

		$products  = $this->wcAdapter->cartGetCartContent();
		$productId = null;
		$maxSizes  = [
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
						$productId = (int) $product['product_id'];
						$maxSizes  = [
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
						$productId = (int) $product['product_id'];
						$maxSizes  = [
							'length' => $productSizes[0],
							'width'  => $productSizes[1],
							'depth'  => $productSizes[2],
						];
					}
				}
			}
		}

		if ( $productId === null || $maxSizes['length'] === 0 ) {
			return null;
		}

		return [
			'productId' => $productId,
			'sizes'     => $maxSizes,
		];
	}

	/**
	 * Names the product the size check actually compared, which is the cart-wide biggest one - with
	 * restriction "dimensions" that need not be every product breaching the limit.
	 *
	 * @return array{productId: int, restriction: string, measured: float|int, limit: float}|null
	 * @throws ProductNotFoundException
	 */
	public function findOversizedProductForCarrier( Carrier\Options $carrierOptions ): ?array {
		$sizeRestrictions = $carrierOptions->getSizeRestrictions();
		if ( $sizeRestrictions === null ) {
			return null;
		}
		$biggestBySum    = $this->findBiggestProduct( self::CRITERIA_BY_SUM );
		$biggestByLength = $this->findBiggestProduct();
		if ( $biggestBySum === null || $biggestByLength === null ) {
			return null;
		}

		if ( isset( $sizeRestrictions['maximum_length'] ) && is_numeric( trim( (string) $sizeRestrictions['maximum_length'] ) ) ) {
			$productMax = max( $biggestByLength['sizes'] );
			if ( $productMax > $sizeRestrictions['maximum_length'] ) {
				return [
					'productId'   => $biggestByLength['productId'],
					'restriction' => self::SIZE_RESTRICTION_MAXIMUM_LENGTH,
					'measured'    => $productMax,
					'limit'       => (float) $sizeRestrictions['maximum_length'],
				];
			}
		}
		if ( isset( $sizeRestrictions['dimensions_sum'] ) && is_numeric( trim( (string) $sizeRestrictions['dimensions_sum'] ) ) ) {
			$productSum = array_sum( $biggestBySum['sizes'] );
			if ( $productSum > $sizeRestrictions['dimensions_sum'] ) {
				return [
					'productId'   => $biggestBySum['productId'],
					'restriction' => self::SIZE_RESTRICTION_DIMENSIONS_SUM,
					'measured'    => $productSum,
					'limit'       => (float) $sizeRestrictions['dimensions_sum'],
				];
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
			$productSizes = $biggestByLength['sizes'];
			rsort( $productSizes, SORT_NUMERIC );

			foreach ( $dimensions as $index => $dimension ) {
				if ( $productSizes[ $index ] > $dimension ) {
					return [
						'productId'   => $biggestByLength['productId'],
						'restriction' => self::SIZE_RESTRICTION_DIMENSIONS,
						'measured'    => $productSizes[ $index ],
						'limit'       => (float) $dimension,
					];
				}
			}
		}

		return null;
	}
}
