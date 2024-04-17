<?php
/**
 * Packeta plugin class for eshop order details.
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Module;

use Packetery\Core\Entity\Order;
use Packetery\Module\Api\Internal\OrderRouter;
use Packetery\Module\Carrier\EntityRepository;
use Packetery\Module\Carrier\OptionPrefixer;
use Packetery\Module\Exception\InvalidCarrierException;
use Packetery\Module\Options\Provider;
use Packetery\Module\Order\Attribute;
use WC_DateTime;
use WC_Order;

/**
 * Class EshopOrderDetails
 *
 * @package Packetery
 */
class EshopOrderDetails {
	/**
	 * Options provider.
	 *
	 * @var Provider
	 */
	private $options_provider;

	/**
	 * Order router.
	 *
	 * @var OrderRouter
	 */
	private $apiRouter;

	/**
	 * Car delivery config.
	 *
	 * @var CarDeliveryConfig
	 */
	private $carDeliveryConfig;

	/**
	 * Carrier Repository.
	 *
	 * @var EntityRepository
	 */
	private $carrierEntityRepository;

	/**
	 * EshopOrderDetails constructor.
	 *
	 * @param Provider          $options_provider Options provider.
	 * @param OrderRouter       $apiRouter Order router.
	 * @param CarDeliveryConfig $carDeliveryConfig Car delivery config.
	 * @param EntityRepository  $carrierEntityRepository Carrier repository.
	 */
	public function __construct(
		Provider $options_provider,
		OrderRouter $apiRouter,
		CarDeliveryConfig $carDeliveryConfig,
		EntityRepository $carrierEntityRepository
	) {
		$this->options_provider        = $options_provider;
		$this->apiRouter               = $apiRouter;
		$this->carDeliveryConfig       = $carDeliveryConfig;
		$this->carrierEntityRepository = $carrierEntityRepository;
	}


	/**
	 * Creates settings for eshop order details script.
	 *
	 * @param Order       $order            Order.
	 * @param WC_Datetime $orderDateCreated Order date created.
	 */
	public function createSettings( Order $order, WC_DateTime $orderDateCreated ): array {
		return [
			/**
			 * Filter widget language in checkout.
			 *
			 * @since 1.4.2
			 */
			'language'                    => (string) apply_filters( 'packeta_widget_language', substr( get_locale(), 0, 2 ) ),
			'isCarDeliverySampleEnabled'  => $this->carDeliveryConfig->isSampleEnabled(),
			'carDeliveryAttrs'            => Attribute::$carDeliveryAttrs,
			'orderId'                     => $order->getNumber(),
			'expeditionDay'               => $this->calculateExpeditionDay( $order, $orderDateCreated ),
			'packeteryApiKey'             => $this->options_provider->get_api_key(),
			'updateCarDeliveryAddressUrl' => $this->apiRouter->getSaveDeliveryAddressUrl(),
			'isSubmittedToPacketa'        => $order->isExported(),
			'appIdentity'                 => Plugin::getAppIdentity(),
			'nonce'                       => wp_create_nonce( 'wp_rest' ),
			'translations'                => [
				'chooseAddress' => __( 'Choose delivery address', 'packeta' ),
			],
		];
	}

	/**
	 * Calculates and returns Expedition Day
	 *
	 * @param Order       $order Order.
	 * @param WC_DateTime $orderDateCreated Order date created.
	 * @return string
	 */
	private function calculateExpeditionDay( Order $order, WC_DateTime $orderDateCreated ): ?string {
		$carrierId = $order->getCarrier()->getId();
		if ( false === $this->carrierEntityRepository->isCarDeliveryCarrier( $carrierId ) ) {
			return null;
		}

		$carrierOptions = Carrier\Options::createByOptionId( OptionPrefixer::getOptionId( $carrierId ) )->toArray();
		$processingDays = $carrierOptions['days_until_shipping'];
		$cutoffTime     = $carrierOptions['shipping_time_cut_off'];

		// Check if a cut-off time is provided and if the current time is after the cut-off time.
		if ( null !== $cutoffTime ) {
			$currentTime = $orderDateCreated->format( 'H:i' );
			if ( $currentTime > $cutoffTime ) {
				// If after cut-off time, move to the next day.
				$orderDateCreated->modify( '+1 day' );
			}
		}

		// Loop through each day to add processing days, skipping weekends.
		for ( $i = 0; $i < $processingDays; $i++ ) {
			// Add a day to the current date.
			$orderDateCreated->modify( '+1 day' );

			// Check if the current day is a weekend (Saturday or Sunday).
			if ( $orderDateCreated->format( 'N' ) >= 6 ) {
				// If it's a weekend, move to the next Monday.
				$orderDateCreated->modify( 'next Monday' );
			}
		}

		// Get the final expedition day.
		return $orderDateCreated->format( 'Y-m-d' );
	}
}
