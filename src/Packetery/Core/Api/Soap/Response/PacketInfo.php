<?php

declare( strict_types=1 );

namespace Packetery\Core\Api\Soap\Response;

class PacketInfo extends BaseResponse {

	private ?string $consignPassword = null;

	public function getConsignPassword(): ?string {
		return $this->consignPassword;
	}

	public function setConsignPassword( ?string $consignPassword ): void {
		$this->consignPassword = $consignPassword;
	}
}
