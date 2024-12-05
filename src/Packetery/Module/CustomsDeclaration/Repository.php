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
use Packetery\Core\CoreHelper;
use Packetery\Module\EntityFactory;
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
	 * @var EntityFactory\CustomsDeclaration
	 */
	private $entityFactory;

	/**
	 * Constructor.
	 *
	 * @param WpdbAdapter                      $wpdbAdapter   Wpdb adapter.
	 * @param EntityFactory\CustomsDeclaration $entityFactory Entity factory.
	 */
	public function __construct( WpdbAdapter $wpdbAdapter, EntityFactory\CustomsDeclaration $entityFactory ) {
		$this->wpdbAdapter   = $wpdbAdapter;
		$this->entityFactory = $entityFactory;
	}

	/**
	 * Gets customs declaration ID by order ID.
	 *
	 * @param string $orderNumber Order ID.
	 * @return string|null
	 */
	private function getIdByOrderNumber( string $orderNumber ): ?string {
		return $this->wpdbAdapter->get_var(
			$this->wpdbAdapter->prepare(
				'SELECT `id` FROM `' . $this->wpdbAdapter->packetery_customs_declaration . '` WHERE `order_id` = %d',
				$orderNumber
			)
		);
	}

	/**
	 * Gets customs declaration by order.
	 *
	 * @param string $orderNumber Order.
	 *
	 * @return CustomsDeclaration|null
	 */
	public function getByOrderNumber( string $orderNumber ): ?CustomsDeclaration {
		$customsDeclarationRow = $this->wpdbAdapter->get_row(
			sprintf(
				'SELECT 
					`id`,
					`order_id`,
					`ead`,
					`delivery_cost`,
					`invoice_number`,
					`invoice_issue_date`,
					`mrn`,
					`invoice_file_id`,
					`ead_file_id`,
					`invoice_file` IS NOT NULL AS `has_invoice_file_content`,
					`ead_file` IS NOT NULL AS `has_ead_file_content`
				FROM `%s`
				WHERE `order_id` = %d',
				$this->wpdbAdapter->packetery_customs_declaration,
				$orderNumber
			),
			ARRAY_A
		);

		if ( null === $customsDeclarationRow ) {
			return null;
		}

		$customsDeclaration = $this->entityFactory->fromStandardizedStructure( $customsDeclarationRow, $orderNumber );

		$customsDeclaration->setInvoiceFile(
			function () use ( $orderNumber ): ?string {
				return $this->wpdbAdapter->get_var(
					$this->wpdbAdapter->prepare(
						'SELECT `invoice_file` FROM `' . $this->wpdbAdapter->packetery_customs_declaration . '` WHERE `order_id` = %d',
						$orderNumber
					)
				);
			},
			(bool) $customsDeclarationRow['has_invoice_file_content']
		);

		$customsDeclaration->setEadFile(
			function () use ( $orderNumber ): ?string {
				return $this->wpdbAdapter->get_var(
					$this->wpdbAdapter->prepare(
						'SELECT `ead_file` FROM `' . $this->wpdbAdapter->packetery_customs_declaration . '` WHERE `order_id` = %d',
						$orderNumber
					)
				);
			},
			(bool) $customsDeclarationRow['has_ead_file_content']
		);

		$customsDeclaration->setItems( $this->getItemsByCustomsDeclarationId( $customsDeclaration->getId() ) );

		return $customsDeclaration;
	}

	/**
	 * Gets customs declaration items by order.
	 *
	 * @param string|null $customsDeclarationId Customs declaration ID.
	 * @return CustomsDeclarationItem[]
	 */
	public function getItemsByCustomsDeclarationId( ?string $customsDeclarationId ): array {
		if ( null === $customsDeclarationId ) {
			return [];
		}

		$customsDeclarationItemRows = $this->wpdbAdapter->get_results(
			sprintf(
				'SELECT
					`id`,
					`customs_declaration_id`,
					`customs_code`,
					`value`,
					`product_name_en`,
					`product_name`,
					`units_count`,
					`country_of_origin`,
					`weight`,
					`is_food_or_book`,
					`is_voc`
				FROM `%s` WHERE `customs_declaration_id` = %d',
				$this->wpdbAdapter->packetery_customs_declaration_item,
				$customsDeclarationId
			),
			ARRAY_A
		);

		if ( null === $customsDeclarationItemRows ) {
			return [];
		}

		$customsDeclarationItems = [];
		foreach ( $customsDeclarationItemRows as $row ) {
			$customsDeclarationItems[] = $this->entityFactory->createItemFromStandardizedStructure( $row );
		}

		return $customsDeclarationItems;
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
		if ( false === $omitInvoiceFile && $customsDeclaration->hasInvoiceFileContent() ) {
			$this->wpdbAdapter->query(
				$this->wpdbAdapter->prepare(
					'UPDATE `' . $this->wpdbAdapter->packetery_customs_declaration . '` SET `invoice_file` = %s WHERE `id` = %d',
					$customsDeclaration->getInvoiceFile(),
					$customsDeclaration->getId()
				)
			);
		}

		if ( false === $omitInvoiceFile && false === $customsDeclaration->hasInvoiceFileContent() ) {
			$this->wpdbAdapter->query(
				$this->wpdbAdapter->prepare(
					'UPDATE ' . $this->wpdbAdapter->packetery_customs_declaration . ' SET `invoice_file` = NULL WHERE `id` = %d',
					$customsDeclaration->getId()
				)
			);
		}

		$omitEadFile = in_array( 'ead_file', $fieldsToOmit, true );
		if ( false === $omitEadFile && $customsDeclaration->hasEadFileContent() ) {
			$this->wpdbAdapter->query(
				$this->wpdbAdapter->prepare(
					'UPDATE `' . $this->wpdbAdapter->packetery_customs_declaration . '` SET `ead_file` = %s WHERE `id` = %d',
					$customsDeclaration->getEadFile(),
					$customsDeclaration->getId()
				)
			);
		}

		if ( false === $omitEadFile && false === $customsDeclaration->hasEadFileContent() ) {
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
	 * Deletes all items.
	 *
	 * @param string $customsDeclarationId Customs Declaration ID.
	 * @return void
	 */
	private function deleteItems( string $customsDeclarationId ): void {
		$items = $this->getItemsByCustomsDeclarationId( $customsDeclarationId );

		if ( empty( $items ) ) {
			return;
		}

		foreach ( $items as $item ) {
			$this->deleteItem( (int) $item->getId() );
		}
	}

	/**
	 * Completely deletes Customs Declaration with all its items.
	 *
	 * @param string $orderId Order ID.
	 * @return void
	 */
	public function delete( string $orderId ): void {
		$customsDeclarationId = $this->getIdByOrderNumber( $orderId );

		if ( null === $customsDeclarationId ) {
			return;
		}

		$this->deleteItems( $customsDeclarationId );
		$this->wpdbAdapter->delete( $this->wpdbAdapter->packetery_customs_declaration, [ 'id' => $customsDeclarationId ], '%d' );
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
			'id'                 => $customsDeclaration->getId(),
			'order_id'           => $customsDeclaration->getOrderId(),
			'ead'                => $customsDeclaration->getEad(),
			'delivery_cost'      => $customsDeclaration->getDeliveryCost(),
			'invoice_number'     => $customsDeclaration->getInvoiceNumber(),
			'invoice_issue_date' => $customsDeclaration->getInvoiceIssueDate()->format( CoreHelper::MYSQL_DATE_FORMAT ),
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
			'id'                     => $customsDeclarationItem->getId(),
			'customs_declaration_id' => $customsDeclarationItem->getCustomsDeclarationId(),
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
				`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`order_id` bigint(20) UNSIGNED NOT NULL,
				`ead` varchar(50) NOT NULL,
				`delivery_cost` decimal(13,2) UNSIGNED NOT NULL,
				`invoice_number` varchar(255) NOT NULL,
				`invoice_issue_date` date NOT NULL,
				`invoice_file` mediumblob NULL DEFAULT NULL,
				`invoice_file_id` varchar(255) NULL DEFAULT NULL,
				`mrn` varchar(32) NULL DEFAULT NULL,
				`ead_file` mediumblob NULL DEFAULT NULL,
				`ead_file_id` varchar(255) NULL DEFAULT NULL,
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
				`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`customs_declaration_id` int(11) UNSIGNED NOT NULL,
				`customs_code` varchar(8) NOT NULL,
				`value` decimal(13,2) UNSIGNED NOT NULL,
				`product_name_en` varchar(255) NOT NULL,
				`product_name` varchar(255) NULL DEFAULT NULL,
				`units_count` int(11) UNSIGNED NOT NULL,
				`country_of_origin` char(2) NOT NULL,
				`weight` decimal(10,3) UNSIGNED NOT NULL,
				`is_food_or_book` tinyint(1) NOT NULL,
				`is_voc` tinyint(1) NOT NULL,
			PRIMARY KEY (`id`)
		) %s',
			$this->wpdbAdapter->packetery_customs_declaration_item,
			$this->wpdbAdapter->get_charset_collate()
		);

		return $this->wpdbAdapter->dbDelta( $createItemTableQuery, $this->wpdbAdapter->packetery_customs_declaration_item );
	}

	/**
	 * Drop table used to store customs declarations items.
	 */
	public function dropItems(): void {
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packetery_customs_declaration_item . '`' );
	}

	/**
	 * Drop table used to store customs declarations.
	 */
	public function drop(): void {
		$this->wpdbAdapter->query( 'DROP TABLE IF EXISTS `' . $this->wpdbAdapter->packetery_customs_declaration . '`' );
	}
}
