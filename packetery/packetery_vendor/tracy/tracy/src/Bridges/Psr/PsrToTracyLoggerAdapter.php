<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bridges\PacketeryPsr;

use PacketeryPsr;
use Tracy;


/**
 * PacketeryPsr\Log\LoggerInterface to Tracy\ILogger adapter.
 */
class PacketeryPsrToTracyLoggerAdapter implements Tracy\ILogger
{
	/** Tracy logger level to PSR-3 log level mapping */
	private const LEVEL_MAP = [
		Tracy\ILogger::DEBUG => PacketeryPsr\Log\LogLevel::DEBUG,
		Tracy\ILogger::INFO => PacketeryPsr\Log\LogLevel::INFO,
		Tracy\ILogger::WARNING => PacketeryPsr\Log\LogLevel::WARNING,
		Tracy\ILogger::ERROR => PacketeryPsr\Log\LogLevel::ERROR,
		Tracy\ILogger::EXCEPTION => PacketeryPsr\Log\LogLevel::ERROR,
		Tracy\ILogger::CRITICAL => PacketeryPsr\Log\LogLevel::CRITICAL,
	];

	/** @var PacketeryPsr\Log\LoggerInterface */
	private $psrLogger;


	public function __construct(PacketeryPsr\Log\LoggerInterface $psrLogger)
	{
		$this->psrLogger = $psrLogger;
	}


	public function log($value, $level = self::INFO)
	{
		if ($value instanceof \Throwable) {
			$message = Tracy\Helpers::getClass($value) . ': ' . $value->getMessage() . ($value->getCode() ? ' #' . $value->getCode() : '') . ' in ' . $value->getFile() . ':' . $value->getLine();
			$context = ['exception' => $value];

		} elseif (!is_string($value)) {
			$message = trim(Tracy\Dumper::toText($value));
			$context = [];

		} else {
			$message = $value;
			$context = [];
		}

		$this->psrLogger->log(
			self::LEVEL_MAP[$level] ?? PacketeryPsr\Log\LogLevel::ERROR,
			$message,
			$context
		);
	}
}
