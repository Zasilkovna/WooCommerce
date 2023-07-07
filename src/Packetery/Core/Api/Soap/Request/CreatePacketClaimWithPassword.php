<?php
/**
 * Class CreatePacket.
 *
 * @package Packetery\Api\Soap\Request
 */

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Request;

use Packetery\Core\Entity;

/**
 * Class CreatePacket.
 * We deliberately don't use this class to send data to the API, but we keep the class for possible later use.
 *
 * @package Packetery\Api\Soap\Request
 */
class CreatePacketClaimWithPassword {

	/**
	 * Number.
	 *
	 * @var string
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
	 * @var float
	 */
	private $value;

	/**
	 * Currency.
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * Eshop.
	 *
	 * @var string
	 */
	private $eshop;

	/**
	 * Consign country.
	 *
	 * @var string
	 */
	private $consignCountry;

	/**
	 * Send email to customer.
	 *
	 * @var bool
	 */
	private $sendEmailToCustomer;

	/**
	 * CreatePacket constructor.
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
	 * @return array
	 */
	public function getSubmittableData(): array {
		return array_filter( get_object_vars( $this ) );
	}

}
