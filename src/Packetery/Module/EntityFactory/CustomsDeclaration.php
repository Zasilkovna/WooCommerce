<?php
/**
 * Class CustomsDeclaration.
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Module\EntityFactory;

use Packetery\Core\Entity\CustomsDeclarationItem;
use Packetery\Core\Entity\Order;

/**
 * Class CustomsDeclaration.
 */
class CustomsDeclaration {

	/**
	 * Creates customs declaration entity from standard structure.
	 *
	 * @param array                        $data Data.
	 * @param \Packetery\Core\Entity\Order $order Order.
	 * @return \Packetery\Core\Entity\CustomsDeclaration
	 */
	public function fromStandardizedStructure( array $data, Order $order ): \Packetery\Core\Entity\CustomsDeclaration {
		$entity = new \Packetery\Core\Entity\CustomsDeclaration(
			$data['id'] ?? null,
			$order,
			$data['ead'],
			(float) $data['delivery_cost'],
			$data['invoice_number'],
			\DateTimeImmutable::createFromFormat(
				\Packetery\Core\Helper::MYSQL_DATE_FORMAT,
				$data['invoice_issue_date'],
				new \DateTimeZone( 'UTC' )
			)
		);
		$entity->setMrn( $data['mrn'] );

		return $entity;
	}

	/**
	 * Creates item from standardized structure.
	 *
	 * @param array                                     $data Data.
	 * @param \Packetery\Core\Entity\CustomsDeclaration $customsDeclaration Customs declaration.
	 * @return CustomsDeclarationItem
	 */
	public function createItemFromStandardizedStructure( array $data, \Packetery\Core\Entity\CustomsDeclaration $customsDeclaration ): CustomsDeclarationItem {
		$entity = new CustomsDeclarationItem(
			$data['id'] ?? null,
			$customsDeclaration,
			$data['customs_code'],
			(float) $data['value'],
			$data['product_name_en'],
			(int) $data['units_count'],
			$data['country_of_origin'],
			(float) $data['weight']
		);
		$entity->setProductName( $data['product_name'] );
		$entity->setIsFoodOrBook( (bool) ( $data['is_food_or_book'] ?? false ) );
		$entity->setIsVoc( (bool) ( $data['is_voc'] ?? false ) );

		return $entity;
	}
}
