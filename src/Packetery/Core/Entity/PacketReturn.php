<?php
/**
 * Class PacketReturn
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Core\Entity;

/**
 * Mutable entity representing a single return (claim) of an order. One order can have multiple returns.
 *
 * Named PacketReturn because "Return" is a reserved word in PHP.
 *
 * @package Packetery
 */
class PacketReturn {

	public const STATUS_PENDING   = 'pending';
	public const STATUS_CREATED   = 'created';
	public const STATUS_CANCELLED = 'cancelled';
	public const STATUS_REJECTED  = 'rejected';

	public const SOURCE_ADMIN    = 'admin';
	public const SOURCE_CUSTOMER = 'customer';

	private ?string $id = null;
	private string $orderId;
	private string $status;
	private string $source;
	private ?string $packetClaimId       = null;
	private ?string $packetClaimPassword = null;
	private ?string $email               = null;
	private ?string $phone               = null;
	private \DateTimeImmutable $createdAt;

	/**
	 * @param string             $orderId   WC order ID.
	 * @param string             $status    Return status, one of self::STATUS_*.
	 * @param string             $source    Who created the return, one of self::SOURCE_*.
	 * @param \DateTimeImmutable $createdAt Creation date.
	 */
	public function __construct(
		string $orderId,
		string $status,
		string $source,
		\DateTimeImmutable $createdAt
	) {
		$this->orderId   = $orderId;
		$this->status    = $status;
		$this->source    = $source;
		$this->createdAt = $createdAt;
	}

	public function getId(): ?string {
		return $this->id;
	}

	public function setId( ?string $id ): void {
		$this->id = $id;
	}

	public function getOrderId(): string {
		return $this->orderId;
	}

	public function getStatus(): string {
		return $this->status;
	}

	public function setStatus( string $status ): void {
		$this->status = $status;
	}

	public function getSource(): string {
		return $this->source;
	}

	public function getPacketClaimId(): ?string {
		return $this->packetClaimId;
	}

	public function setPacketClaimId( ?string $packetClaimId ): void {
		$this->packetClaimId = $packetClaimId;
	}

	public function getPacketClaimPassword(): ?string {
		return $this->packetClaimPassword;
	}

	public function setPacketClaimPassword( ?string $packetClaimPassword ): void {
		$this->packetClaimPassword = $packetClaimPassword;
	}

	public function getEmail(): ?string {
		return $this->email;
	}

	public function setEmail( ?string $email ): void {
		$this->email = $email;
	}

	public function getPhone(): ?string {
		return $this->phone;
	}

	public function setPhone( ?string $phone ): void {
		$this->phone = $phone;
	}

	public function getCreatedAt(): \DateTimeImmutable {
		return $this->createdAt;
	}
}
