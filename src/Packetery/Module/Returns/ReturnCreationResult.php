<?php
/**
 * Class ReturnCreationResult.
 *
 * @package Packetery
 */

declare( strict_types=1 );

namespace Packetery\Module\Returns;

use Packetery\Core\Api\Soap\Response\CreatePacketClaimWithPassword;
use Packetery\Core\Entity\PacketReturn;

/**
 * Outcome of {@see ReturnService::createReturn()}.
 */
class ReturnCreationResult {

	private CreatePacketClaimWithPassword $response;
	private bool $orderSaved;
	private ?PacketReturn $packetReturn;

	/**
	 * @param CreatePacketClaimWithPassword $response     API response.
	 * @param bool                          $orderSaved   Whether the order row was persisted (false only on DB error).
	 * @param PacketReturn|null             $packetReturn Created return, null when the API call faulted.
	 */
	public function __construct(
		CreatePacketClaimWithPassword $response,
		bool $orderSaved,
		?PacketReturn $packetReturn
	) {
		$this->response     = $response;
		$this->orderSaved   = $orderSaved;
		$this->packetReturn = $packetReturn;
	}

	public function getResponse(): CreatePacketClaimWithPassword {
		return $this->response;
	}

	public function hasFault(): bool {
		return $this->response->hasFault();
	}

	public function isOrderSaved(): bool {
		return $this->orderSaved;
	}

	public function getPacketReturn(): ?PacketReturn {
		return $this->packetReturn;
	}
}
