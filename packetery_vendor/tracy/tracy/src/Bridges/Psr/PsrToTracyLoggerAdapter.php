<?php

/**
 * This file is part of the PacketeryTracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace PacketeryTracy\Bridges\PacketeryPsr;

use PacketeryPsr;
use PacketeryTracy;


/**
 * PacketeryPsr\Log\LoggerInterface to PacketeryTracy\ILogger adapter.
 */
class PacketeryPsrToPacketeryTracyLoggerAdapter implements PacketeryTracy\ILogger
{
	/** PacketeryTracy logger level to PSR-3 log level mapping */
	private const LEVEL_MAP = [
		PacketeryTracy\ILogger::DEBUG => PacketeryPsr\Log\LogLevel::DEBUG,
		PacketeryTracy\ILogger::INFO => PacketeryPsr\Log\LogLevel::INFO,
		PacketeryTracy\ILogger::WARNING => PacketeryPsr\Log\LogLevel::WARNING,
		PacketeryTracy\ILogger::ERROR => PacketeryPsr\Log\LogLevel::ERROR,
		PacketeryTracy\ILogger::EXCEPTION => PacketeryPsr\Log\LogLevel::ERROR,
		PacketeryTracy\ILogger::CRITICAL => PacketeryPsr\Log\LogLevel::CRITICAL,
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
			$message = PacketeryTracy\Helpers::getClass($value) . ': ' . $value->getMessage() . ($value->getCode() ? ' #' . $value->getCode() : '') . ' in ' . $value->getFile() . ':' . $value->getLine();
			$context = ['exception' => $value];

		} elseif (!is_string($value)) {
			$message = trim(PacketeryTracy\Dumper::toText($value));
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
