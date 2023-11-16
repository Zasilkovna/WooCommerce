<?php
/**
 * Class CreatePacketClaimWithPassword.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Request;

use Packetery\Core\Entity;

/**
 * Class CreatePacketClaimWithPassword.
 *
 * @package Packetery
 */
class CreatePacketClaimWithPassword {

	/**
	 * Number.
	 *
	 * @var string|null
	 */
	private $number;

	/**
	 * Email.
	 *
	 * @var string|null
	 */
	private $email;

	/**
	 * Phone.
	 *
	 * @var string|null
	 */
	private $phone;

	/**
	 * Value.
	 *
	 * @var float|null
	 */
	private $value;

	/**
	 * Currency.
	 *
	 * @var string|null
	 */
	private $currency;

	/**
	 * Eshop.
	 *
	 * @var string|null
	 */
	private $eshop;

	/**
	 * Consign country.
	 *
	 * @var string|null
	 */
	private $consignCountry;

	/**
	 * Send email to customer.
	 *
	 * @var bool
	 */
	private $sendEmailToCustomer;

	/**
	 * Constructor.
	 *
	 * @param Entity\Order $order Order entity.
	 */
	public function __construct( Entity\Order $order ) {
		$this->number              = ( $order->getCustomNumber() ?? $order->getNumber() );
		$this->email               = $order->getEmail();
		$this->phone               = $order->getPhone();
		$this->value               = $order->getValue();
		$this->currency            = $order->getCurrency();
		$this->eshop               = $order->getEshop();
		$this->consignCountry      = $order->getShippingCountry();
		$this->sendEmailToCustomer = false;
	}

	/**
	 * Gets submittable data.
	 *
	 * @return array<string, string|float|bool|null>
	 */
	public function getSubmittableData(): array {
		return array_filter( get_object_vars( $this ) );
	}

}
