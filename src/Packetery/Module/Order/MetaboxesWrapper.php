<?php
/**
 * Class MetaboxesWrapper.
 *
 * @package Packetery
 */

declare( strict_types=1 );


namespace Packetery\Module\Order;

use Packetery\Module\Exception\InvalidCarrierException;

/**
 * Class MetaboxesWrapper.
 */
class MetaboxesWrapper {

	/**
	 * General order metabox.
	 *
	 * @var Metabox
	 */
	private $generalMetabox;

	/**
	 * Customs declaration metabox.
	 *
	 * @var CustomsDeclarationMetabox
	 */
	private $customDeclarationMetabox;

	/**
	 * Order repository.
	 *
	 * @var Repository
	 */
	private $orderRepository;

	/**
	 * Constructor.
	 *
	 * @param Metabox                   $generalMetabox           General metabox.
	 * @param CustomsDeclarationMetabox $customDeclarationMetabox Customs declaration metabox.
	 * @param Repository                $orderRepository          Order repository.
	 */
	public function __construct(
		Metabox $generalMetabox,
		CustomsDeclarationMetabox $customDeclarationMetabox,
		Repository $orderRepository
	) {
		$this->generalMetabox           = $generalMetabox;
		$this->customDeclarationMetabox = $customDeclarationMetabox;
		$this->orderRepository          = $orderRepository;
	}

	/**
	 * Registers metaboxes.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->generalMetabox->register();
		$this->customDeclarationMetabox->register();
	}

	/**
	 * Saves metabox fields.
	 *
	 * @param int|mixed $wcOrderId Order ID.
	 *
	 * @return void
	 * @throws \WC_Data_Exception When invalid data are passed during shipping address update.
	 */
	public function saveFields( $wcOrderId ): void {
		$order = $this->orderRepository->getById( (int) $wcOrderId, true );

		if ( null === $order ) {
			return;
		}

		$this->generalMetabox->saveFields( $order );
		$this->customDeclarationMetabox->saveFields( $order );
	}
}
