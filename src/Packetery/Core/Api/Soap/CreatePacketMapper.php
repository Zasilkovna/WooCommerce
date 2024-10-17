<?php
/**
 * Class CreatePacketMapper.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap;

use Packetery\Core\Entity;
use Packetery\Core\CoreHelper;

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
	 * CreatePacketMapper constructor.
	 *
	 * @param CoreHelper $coreHelper CoreHelper.
	 */
	public function __construct( CoreHelper $coreHelper ) {
		$this->coreHelper = $coreHelper;
	}

	/**
	 * Maps order data to CreatePacket structure.
	 *
	 * @param Entity\Order $order Order entity.
	 * @return array<string, mixed>
	 */
	public function fromOrderToArray( Entity\Order $order ): array {
		$createPacketData = [
			// Required attributes.
			'number'       => ( $order->getCustomNumber() ?? $order->getNumber() ),
			'name'         => $order->getName(),
			'surname'      => $order->getSurname(),
			'value'        => $order->getValue(),
			'weight'       => $order->getFinalWeight(),
			'addressId'    => $order->getPickupPointOrCarrierId(),
			'eshop'        => $order->getEshop(),
			// Optional attributes.
			'adultContent' => (int) $order->containsAdultContent(),
			'cod'          => $order->getCod(),
			'currency'     => $order->getCurrency(),
			'email'        => $order->getEmail(),
			'note'         => $order->getNote(),
			'phone'        => $order->getPhone(),
			'deliverOn'    => $this->coreHelper->getStringFromDateTime( $order->getDeliverOn(), CoreHelper::DATEPICKER_FORMAT ),
		];

		$pickupPoint = $order->getPickupPoint();
		if ( null !== $pickupPoint && $order->isExternalCarrier() ) {
			$createPacketData['carrierPickupPoint'] = $pickupPoint->getId();
		}

		if ( $order->isHomeDelivery() || $order->isCarDelivery() ) {
			$address = $order->getDeliveryAddress();
			if ( null !== $address ) {
				$createPacketData['street'] = $address->getStreet();
				$createPacketData['city']   = $address->getCity();
				$createPacketData['zip']    = $address->getZip();
				if ( $address->getHouseNumber() ) {
					$createPacketData['houseNumber'] = $address->getHouseNumber();
				}
			}
		}

		$carrier = $order->getCarrier();
		if ( $carrier->requiresSize() ) {
			$size = $order->getSize();
			if ( null !== $size ) {
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

		if ( false === $carrier->requiresCustomsDeclarations() ) {
			return $createPacketData;
		}

		$customsDeclaration = $order->getCustomsDeclaration();
		if ( null === $customsDeclaration ) {
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

		if ( null !== $customsDeclaration->getMrn() ) {
			$createPacketData['attributes'][] = [
				'key'   => 'mrn',
				'value' => $customsDeclaration->getMrn(),
			];
		}

		if ( null !== $customsDeclaration->getEadFileId() ) {
			$createPacketData['attributes'][] = [
				'key'   => 'eadFile',
				'value' => $customsDeclaration->getEadFileId(),
			];
		}

		if ( null !== $customsDeclaration->getInvoiceFileId() ) {
			$createPacketData['attributes'][] = [
				'key'   => 'invoiceFile',
				'value' => $customsDeclaration->getInvoiceFileId(),
			];
		}

		return $createPacketData;
	}

}
