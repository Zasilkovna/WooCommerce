<?php
/**
 * Class Repository.
 *
 * @package Packetery
 */

declare(strict_types=1);

namespace Packetery\Module\CustomsDeclaration;

use Packetery\Core\Entity\CustomsDeclaration;
use Packetery\Core\Entity\CustomsDeclarationItem;
use Packetery\Core\Entity\Order;
use Packetery\Core\Helper;
use Packetery\Module\WpdbAdapter;

/**
 * Class Repository.
 */
class Repository {

	/**
	 * Wpdb adapter.
	 *
	 * @var WpdbAdapter
	 */
	private $wpdbAdapter;

	/**
	 * Entity factory.
	 *
	 * @var \Packetery\Module\EntityFactory\CustomsDeclaration
	 */
	private $entityFactory;

	/**
	 * Constructor.
	 *
	 * @param WpdbAdapter                                        $wpdbAdapter Wpdb adapter.
	 * @param \Packetery\Module\EntityFactory\CustomsDeclaration $entityFactory Entity factory.
	 */
	public function __construct( WpdbAdapter $wpdbAdapter, \Packetery\Module\EntityFactory\CustomsDeclaration $entityFactory ) {
		$this->wpdbAdapter   = $wpdbAdapter;
		$this->entityFactory = $entityFactory;
	}

	/**
	 * Gets customs declaration by order.
	 *
	 * @param Order $order Order.
	 * @return CustomsDeclaration|null
	 */
	public function getByOrder( Order $order ): ?CustomsDeclaration {
		$row = $this->wpdbAdapter->get_row(
			sprintf(
				'SELECT 
                            id, order_id, ead, delivery_cost, invoice_number, invoice_issue_date, mrn, invoice_file_id, ead_file_id
                        FROM `%s`
                        WHERE `order_id` = %d',
				$this->wpdbAdapter->packetery_customs_declaration,
				$order->getNumber()
			),
			ARRAY_A
		);

		if ( null === $row ) {
			return null;
		}

		$result = $this->entityFactory->fromStandardizedStructure( $row, $order );

		$result->setInvoiceFile(
			function () use ( $order ): ?string {
				return $this->wpdbAdapter->get_var(
					$this->wpdbAdapter->prepare(
						'SELECT invoice_file FROM `' . $this->wpdbAdapter->packetery_customs_declaration . '` WHERE `order_id` = %d',
						$order->getNumber()
					)
				);
			}
		);

		$result->setEadFile(
			function () use ( $order ): ?string {
				return $this->wpdbAdapter->get_var(
					$this->wpdbAdapter->prepare(
						'SELECT ead_file FROM `' . $this->wpdbAdapter->packetery_customs_declaration . '` WHERE `order_id` = %d',
						$order->getNumber()
					)
				);
			}
		);

		return $result;
	}

	/**
	 * Has invoice file.
	 *
	 * @param CustomsDeclaration|null $customsDeclaration Customs declaration.
	 * @return bool
	 */
	public function hasInvoiceFile( ?CustomsDeclaration $customsDeclaration ): bool {
		if ( null === $customsDeclaration ) {
			return false;
		}

		return '1' !== $this->wpdbAdapter->get_var(
			$this->wpdbAdapter->prepare(
				'SELECT "1" FROM `' . $this->wpdbAdapter->packetery_customs_declaration . '` WHERE `id` = %d AND `invoice_file` IS NULL',
				$customsDeclaration->getId()
			)
		);
	}

	/**
	 * Has EAD file.
	 *
	 * @param CustomsDeclaration|null $customsDeclaration Customs declaration.
	 * @return bool
	 */
	public function hasEadFile( ?CustomsDeclaration $customsDeclaration ): bool {
		if ( null === $customsDeclaration ) {
			return false;
		}

		return '1' !== $this->wpdbAdapter->get_var(
			$this->wpdbAdapter->prepare(
				'SELECT "1" FROM `' . $this->wpdbAdapter->packetery_customs_declaration . '` WHERE `id` = %d AND `ead_file` IS NULL',
				$customsDeclaration->getId()
			)
		);
	}

	/**
	 * Gets customs declaration items by order.
	 *
	 * @param CustomsDeclaration $customsDeclaration Order.
	 * @return CustomsDeclarationItem[]
	 */
	public function getItemsByCustomsDeclaration( CustomsDeclaration $customsDeclaration ): array {
		if ( null === $customsDeclaration->getId() ) {
			return [];
		}

		$rows = $this->wpdbAdapter->get_results(
			sprintf(
				'SELECT * FROM `%s` WHERE `customs_declaration_id` = %d',
				$this->wpdbAdapter->packetery_customs_declaration_item,
				$customsDeclaration->getId()
			),
			ARRAY_A
		);

		if ( null === $rows ) {
			return [];
		}

		$results = [];
		foreach ( $rows as $row ) {
			$results[] = $this->entityFactory->createItemFromStandardizedStructure(
				$row,
				$customsDeclaration
			);
		}

		return $results;
	}

	/**
	 * Saves customs declaration.
	 *
	 * @param CustomsDeclaration $customsDeclaration Customs declaration.
	 * @param array              $fieldsToOmit Fields to omit.
	 * @return void
	 */
	public function save( CustomsDeclaration $customsDeclaration, array $fieldsToOmit = [ 'invoice_file', 'ead_file' ] ): void {
		if ( null === $customsDeclaration->getId() ) {
			$this->wpdbAdapter->insertReplaceHelper(
				$this->wpdbAdapter->packetery_customs_declaration,
				$this->declarationToDbArray( $customsDeclaration, $fieldsToOmit )
			);
			$customsDeclaration->setId( $this->wpdbAdapter->getLastInsertId() );
		} else {
			$this->wpdbAdapter->update(
				$this->wpdbAdapter->packetery_customs_declaration,
				$this->declarationToDbArray( $customsDeclaration, $fieldsToOmit ),
				[ 'id' => (int) $customsDeclaration->getId() ]
			);
		}

		$omitInvoiceFile = in_array( 'invoice_file', $fieldsToOmit, true );
		if ( false === $omitInvoiceFile && $customsDeclaration->hasInvoiceFile() ) {
			$this->wpdbAdapter->query(
				$this->wpdbAdapter->prepare(
					'UPDATE `' . $this->wpdbAdapter->packetery_customs_declaration . '` SET `invoice_file` = %s WHERE `id` = %d',
					$customsDeclaration->getInvoiceFile(),
					$customsDeclaration->getId()
				)
			);
		}

		if ( false === $omitInvoiceFile && false === $customsDeclaration->hasInvoiceFile() ) {
			$this->wpdbAdapter->query(
				$this->wpdbAdapter->prepare(
					'UPDATE ' . $this->wpdbAdapter->packetery_customs_declaration . ' SET `invoice_file` = NULL WHERE `id` = %d',
					$customsDeclaration->getId()
				)
			);
		}

		$omitEadFile = in_array( 'ead_file', $fieldsToOmit, true );
		if ( false === $omitEadFile && $customsDeclaration->hasEadFile() ) {
			$this->wpdbAdapter->query(
				$this->wpdbAdapter->prepare(
					'UPDATE `' . $this->wpdbAdapter->packetery_customs_declaration . '` SET `ead_file` = %s WHERE `id` = %d',
					$customsDeclaration->getEadFile(),
					$customsDeclaration->getId()
				)
			);
		}

		if ( false === $omitEadFile && false === $customsDeclaration->hasEadFile() ) {
			$this->wpdbAdapter->query(
				$this->wpdbAdapter->prepare(
					'UPDATE ' . $this->wpdbAdapter->packetery_customs_declaration . ' SET `ead_file` = NULL WHERE `id` = %d',
					$customsDeclaration->getId()
				)
			);
		}
	}

	/**
	 * Saves customs declaration item.
	 *
	 * @param CustomsDeclarationItem $customsDeclarationItem Customs declaration item.
	 * @return void
	 */
	public function saveItem( CustomsDeclarationItem $customsDeclarationItem ): void {
		if ( null === $customsDeclarationItem->getId() ) {
			$this->wpdbAdapter->insert(
				$this->wpdbAdapter->packetery_customs_declaration_item,
				$this->declarationItemToDbArray( $customsDeclarationItem )
			);
			$customsDeclarationItem->setId( $this->wpdbAdapter->getLastInsertId() );
		} else {
			$this->wpdbAdapter->update(
				$this->wpdbAdapter->packetery_customs_declaration_item,
				$this->declarationItemToDbArray( $customsDeclarationItem ),
				[ 'id' => (int) $customsDeclarationItem->getId() ]
			);
		}
	}

	/**
	 * Deletes item.
	 *
	 * @param int $itemId Item ID.
	 * @return void
	 */
	public function deleteItem( int $itemId ): void {
		$this->wpdbAdapter->delete( $this->wpdbAdapter->packetery_customs_declaration_item, [ 'id' => $itemId ], '%d' );
	}

	/**
	 * Converts customs declaration to DB array.
	 *
	 * @param CustomsDeclaration $customsDeclaration Customs declaration.
	 * @param array              $fieldsToOmit Fields to omit.
	 * @return array<string, string|int|float>
	 */
	public function declarationToDbArray( CustomsDeclaration $customsDeclaration, array $fieldsToOmit = [] ): array {
		$data = [
			'id'                 => (int) $customsDeclaration->getId(),
			'order_id'           => (int) $customsDeclaration->getOrder()->getNumber(),
			'ead'                => $customsDeclaration->getEad(),
			'delivery_cost'      => $customsDeclaration->getDeliveryCost(),
			'invoice_number'     => $customsDeclaration->getInvoiceNumber(),
			'invoice_issue_date' => $customsDeclaration->getInvoiceIssueDate()->format( Helper::MYSQL_DATE_FORMAT ),
			'invoice_file_id'    => $customsDeclaration->getInvoiceFileId(),
			'mrn'                => $customsDeclaration->getMrn(),
			'ead_file_id'        => $customsDeclaration->getEadFileId(),
		];

		foreach ( $fieldsToOmit as $fieldToOmit ) {
			unset( $data[ $fieldToOmit ] );
		}

		return $data;
	}

	/**
	 * Converts customs declaration item to DB array.
	 *
	 * @param CustomsDeclarationItem $customsDeclarationItem Customs declaration item.
	 * @return array<string, string|int|float>
	 */
	public function declarationItemToDbArray( CustomsDeclarationItem $customsDeclarationItem ): array {
		return [
			'id'                     => (int) $customsDeclarationItem->getId(),
			'customs_declaration_id' => (int) $customsDeclarationItem->getCustomsDeclaration()->getId(),
			'customs_code'           => $customsDeclarationItem->getCustomsCode(),
			'value'                  => $customsDeclarationItem->getValue(),
			'product_name_en'        => $customsDeclarationItem->getProductNameEn(),
			'product_name'           => $customsDeclarationItem->getProductName(),
			'units_count'            => $customsDeclarationItem->getUnitsCount(),
			'country_of_origin'      => strtoupper( $customsDeclarationItem->getCountryOfOrigin() ),
			'weight'                 => $customsDeclarationItem->getWeight(),
			'is_food_or_book'        => (int) $customsDeclarationItem->isFoodOrBook(),
			'is_voc'                 => (int) $customsDeclarationItem->isVoc(),
		];
	}

	/**
	 * Creates main table.
	 *
	 * @return bool
	 */
	public function createOrAlterTable(): bool {
		$createTableQuery = sprintf(
			'CREATE TABLE `%s` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `order_id` BIGINT(20) UNSIGNED NOT NULL,
            `ead` VARCHAR(50) NOT NULL,
            `delivery_cost` DECIMAL(13,2) UNSIGNED NOT NULL,
            `invoice_number` VARCHAR(255) NOT NULL,
            `invoice_issue_date` DATE NOT NULL,
            `invoice_file` MEDIUMBLOB NULL DEFAULT NULL,
            `invoice_file_id` VARCHAR(255) NULL DEFAULT NULL,
            `mrn` VARCHAR(32) NULL DEFAULT NULL,
            `ead_file` MEDIUMBLOB NULL DEFAULT NULL,
            `ead_file_id` VARCHAR(255) NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) %s',
			$this->wpdbAdapter->packetery_customs_declaration,
			$this->wpdbAdapter->get_charset_collate()
		);

		return $this->wpdbAdapter->dbDelta( $createTableQuery, $this->wpdbAdapter->packetery_customs_declaration );
	}

	/**
	 * Creates item table.
	 *
	 * @return bool
	 */
	public function createOrAlterItemTable(): bool {
		$createItemTableQuery = sprintf(
			'CREATE TABLE `%s` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `customs_declaration_id` INT(11) UNSIGNED NOT NULL,
            `customs_code` VARCHAR(8) NOT NULL,
            `value` DECIMAL(13,2) UNSIGNED NOT NULL,
            `product_name_en` VARCHAR(255) NOT NULL,
            `product_name` VARCHAR(255) NULL DEFAULT NULL,
            `units_count` INT(11) UNSIGNED NOT NULL,
            `country_of_origin` CHAR(2) NOT NULL,
            `weight` DECIMAL(10,3) UNSIGNED NOT NULL,
            `is_food_or_book` TINYINT(1) NOT NULL,
            `is_voc` TINYINT(1) NOT NULL,
            PRIMARY KEY (`id`)
        ) %s',
			$this->wpdbAdapter->packetery_customs_declaration_item,
			$this->wpdbAdapter->get_charset_collate()
		);

		return $this->wpdbAdapter->dbDelta( $createItemTableQuery, $this->wpdbAdapter->packetery_customs_declaration_item );
	}
}
