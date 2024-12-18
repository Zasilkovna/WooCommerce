<?php

namespace Packetery\Module\Forms\FormData;

class OrderFormData {

	/**
	 * @var float|null
	 */
	public $packeteryWeight;

	/**
	 * @var string|null
	 */
	public $packeteryOriginalWeight;

	/**
	 * @var int|float|null
	 */
	public $packeteryLength;

	/**
	 * @var int|float|null
	 */
	public $packeteryWidth;

	/**
	 * @var int|float|null
	 */
	public $packeteryHeight;

	/**
	 * @var bool
	 */
	public $packeteryAdultContent;

	/**
	 * @var float|null
	 */
	public $packeteryCOD;

	/**
	 * @var float|null
	 */
	public $packeteryValue;

	/**
	 * @var string|null
	 */
	public $packeteryDeliverOn;

	/**
	 * @param float|null     $packeteryWeight
	 * @param string|null    $packeteryOriginalWeight
	 * @param int|float|null $packeteryLength
	 * @param int|float|null $packeteryWidth
	 * @param int|float|null $packeteryHeight
	 * @param bool           $packeteryAdultContent
	 * @param float|null     $packeteryCOD
	 * @param float|null     $packeteryValue
	 * @param string|null    $packeteryDeliverOn
	 */
	public function __construct(
		?float $packeteryWeight,
		?string $packeteryOriginalWeight,
		$packeteryLength,
		$packeteryWidth,
		$packeteryHeight,
		bool $packeteryAdultContent,
		?float $packeteryCOD,
		?float $packeteryValue,
		?string $packeteryDeliverOn
	) {
		$this->packeteryWeight         = $packeteryWeight;
		$this->packeteryOriginalWeight = $packeteryOriginalWeight;
		$this->packeteryLength         = $packeteryLength;
		$this->packeteryWidth          = $packeteryWidth;
		$this->packeteryHeight         = $packeteryHeight;
		$this->packeteryAdultContent   = $packeteryAdultContent;
		$this->packeteryCOD            = $packeteryCOD;
		$this->packeteryValue          = $packeteryValue;
		$this->packeteryDeliverOn      = $packeteryDeliverOn;
	}
}
