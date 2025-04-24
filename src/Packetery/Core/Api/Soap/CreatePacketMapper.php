<?php
/**
 * Class CreatePacketMapper.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap;

use Packetery\Core\CoreHelper;
use Packetery\Core\Entity;
use Packetery\Core\Rounder;
use Packetery\Module\Carrier\CarrierOptionsFactory;

/**
 * Class CreatePacketMapper.
 *
 * @package Packetery
 */
class CreatePacketMapper {

	/**
	 * CoreHelper.
	 *
	 * @var CoreHelper
	 */
	private $coreHelper;

	/**
	 * @var CarrierOptionsFactory
	 */
	private $carrierOptionsFactory;

	/**
	 * @var string
	 */
	private $affiliateId;

	public function __construct(
		CoreHelper $coreHelper,
		CarrierOptionsFactory $carrierOptionsFactory,
		string $affiliateId
	) {
		$this->coreHelper            = $coreHelper;
		$this->carrierOptionsFactory = $carrierOptionsFactory;
		$this->affiliateId           = $affiliateId;
	}

	/**
	 * Maps order data to CreatePacket structure.
	 *
	 * @param Entity\Order $order Order entity.
	 *
	 * @return array<string, array<int<0, max>|string, array<string, array<int, array<string, bool|float|int|string|null>>|float|string|null>|float|null>|float|int|string|null>
	 */
	public function fromOrderToArray( Entity\Order $order ): array {
		$createPacketData = [
			// Required attributes.
			'number'       => $order->getCustomNumberOrNumber(),
			'name'         => $order->getName(),
			'surname'      => $order->getSurname(),
			'value'        => $order->getFinalValue(),
			'weight'       => $order->getFinalWeight(),
			'addressId'    => $order->getPickupPointOrCarrierId(),
			'eshop'        => $order->getEshop(),
			// Optional attributes.
			'adultContent' => (int) $order->containsAdultContent(),
			'cod'          => null,
			'currency'     => $order->getCurrency(),
			'email'        => $order->getEmail(),
			'note'         => $order->getNote(),
			'phone'        => $order->getPhone(),
			'deliverOn'    => $this->coreHelper->getStringFromDateTime( $order->getDeliverOn(), CoreHelper::DATEPICKER_FORMAT ),
			'affiliateId'  => $this->affiliateId,
		];

		$codValue = $order->getFinalCod();
		if ( $codValue !== null ) {
			$roundingType            = $this->carrierOptionsFactory->createByCarrierId( $order->getCarrier()->getId() )->getCodRoundingType();
			$roundedCod              = Rounder::roundByCurrency( $codValue, $createPacketData['currency'], $roundingType );
			$createPacketData['cod'] = $roundedCod;
		}

		$pickupPoint = $order->getPickupPoint();
		if ( $pickupPoint !== null && $order->isExternalCarrier() ) {
			$createPacketData['carrierPickupPoint'] = $pickupPoint->getId();
		}

		if ( $order->isHomeDelivery() || $order->isCarDelivery() ) {
			$address = $order->getDeliveryAddress();
			if ( $address !== null ) {
				$createPacketData['street'] = $address->getStreet();
				$createPacketData['city']   = $address->getCity();
				$createPacketData['zip']    = $address->getZip();
				if ( $address->getHouseNumber() !== null ) {
					$createPacketData['houseNumber'] = $address->getHouseNumber();
				}
			}
		}

		$carrier = $order->getCarrier();
		if ( $carrier->requiresSize() ) {
			$size = $order->getSize();
			if ( $size !== null ) {
				$createPacketData['size'] = [
					'length' => $size->getLength(),
					'width'  => $size->getWidth(),
					'height' => $size->getHeight(),
				];
			}
		}

		$createPacketData['attributes'] = [];

		if ( $order->isCarDelivery() ) {
			$createPacketData['attributes']['attribute'] = [
				'key'   => 'carDeliveryId',
				'value' => $order->getCarDeliveryId(),
			];
		}

		if ( $carrier->requiresCustomsDeclarations() === false ) {
			return $createPacketData;
		}

		$customsDeclaration = $order->getCustomsDeclaration();
		if ( $customsDeclaration === null ) {
			return $createPacketData;
		}

		$createPacketData['items'] = [];
		foreach ( $customsDeclaration->getItems() as $customsDeclarationItem ) {
			$createPacketData['items'][] = [
				'attributes' => [
					[
						'key'   => 'countryOfOrigin',
						'value' => $customsDeclarationItem->getCountryOfOrigin(),
					],
					[
						'key'   => 'customsCode',
						'value' => $customsDeclarationItem->getCustomsCode(),
					],
					[
						'key'   => 'productName',
						'value' => $customsDeclarationItem->getProductName(),
					],
					[
						'key'   => 'productNameEn',
						'value' => $customsDeclarationItem->getProductNameEn(),
					],
					[
						'key'   => 'value',
						'value' => $customsDeclarationItem->getValue(),
					],
					[
						'key'   => 'unitsCount',
						'value' => $customsDeclarationItem->getUnitsCount(),
					],
					[
						'key'   => 'weight',
						'value' => $customsDeclarationItem->getWeight(),
					],
					[
						'key'   => 'isFoodBook',
						'value' => $customsDeclarationItem->isFoodOrBook(),
					],
					[
						'key'   => 'isVoc',
						'value' => $customsDeclarationItem->isVoc(),
					],
				],
			];
		}

		$createPacketData['attributes'][] = [
			'key'   => 'ead',
			'value' => $customsDeclaration->getEad(),
		];
		$createPacketData['attributes'][] = [
			'key'   => 'deliveryCost',
			'value' => $customsDeclaration->getDeliveryCost(),
		];
		$createPacketData['attributes'][] = [
			'key'   => 'invoiceNumber',
			'value' => $customsDeclaration->getInvoiceNumber(),
		];
		$createPacketData['attributes'][] = [
			'key'   => 'invoiceIssueDate',
			'value' => $customsDeclaration->getInvoiceIssueDate()->format( 'Y-m-d' ),
		];

		if ( $customsDeclaration->getMrn() !== null ) {
			$createPacketData['attributes'][] = [
				'key'   => 'mrn',
				'value' => $customsDeclaration->getMrn(),
			];
		}

		if ( $customsDeclaration->getEadFileId() !== null ) {
			$createPacketData['attributes'][] = [
				'key'   => 'eadFile',
				'value' => $customsDeclaration->getEadFileId(),
			];
		}

		if ( $customsDeclaration->getInvoiceFileId() !== null ) {
			$createPacketData['attributes'][] = [
				'key'   => 'invoiceFile',
				'value' => $customsDeclaration->getInvoiceFileId(),
			];
		}

		return $createPacketData;
	}
}
