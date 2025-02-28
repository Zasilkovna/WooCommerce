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
	 * @var string|null
	 */
	public $packeteryCalculatedCod;

	/**
	 * @var float|null
	 */
	public $packeteryValue;

	/**
	 * @var string|null
	 */
	public $packeteryCalculatedValue;

	/**
	 * @var string|null
	 */
	public $packeteryDeliverOn;

}
