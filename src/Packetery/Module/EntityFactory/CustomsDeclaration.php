<?php
/**
 * Class CustomsDeclaration.
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Module\EntityFactory;

use Packetery\Core\Entity;
use Packetery\Core\CoreHelper;

/**
 * Class CustomsDeclaration.
 */
class CustomsDeclaration {

	/**
	 * Creates customs declaration entity from standard structure.
	 *
	 * @param array  $data    Data.
	 * @param string $orderId Order ID.
	 *
	 * @return Entity\CustomsDeclaration
	 */
	public function fromStandardizedStructure( array $data, string $orderId ): Entity\CustomsDeclaration {
		$entity = new Entity\CustomsDeclaration(
			$orderId,
			$data['ead'],
			(float) $data['delivery_cost'],
			$data['invoice_number'],
			\DateTimeImmutable::createFromFormat(
				CoreHelper::MYSQL_DATE_FORMAT,
				$data['invoice_issue_date']
			)
		);
		$entity->setId( $data['id'] ?? null );
		$entity->setInvoiceFileId( $data['invoice_file_id'] );
		$entity->setMrn( $data['mrn'] );
		$entity->setEadFileId( $data['ead_file_id'] );

		return $entity;
	}

	/**
	 * Creates item from standardized structure.
	 *
	 * @param array $data Data.
	 * @return Entity\CustomsDeclarationItem
	 */
	public function createItemFromStandardizedStructure( array $data ): Entity\CustomsDeclarationItem {
		$entity = new Entity\CustomsDeclarationItem(
			$data['customs_declaration_id'],
			$data['customs_code'],
			(float) $data['value'],
			$data['product_name_en'],
			(int) $data['units_count'],
			$data['country_of_origin'],
			(float) $data['weight']
		);
		$entity->setId( $data['id'] ?? null );
		$entity->setProductName( $data['product_name'] );
		$entity->setIsFoodOrBook( (bool) ( $data['is_food_or_book'] ?? false ) );
		$entity->setIsVoc( (bool) ( $data['is_voc'] ?? false ) );

		return $entity;
	}
}
