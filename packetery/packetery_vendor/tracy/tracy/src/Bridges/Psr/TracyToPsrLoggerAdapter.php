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
 * PacketeryTracy\ILogger to PacketeryPsr\Log\LoggerInterface adapter.
 */
class PacketeryTracyToPacketeryPsrLoggerAdapter extends PacketeryPsr\Log\AbstractLogger
{
	/** PSR-3 log level to PacketeryTracy logger level mapping */
	private const LEVEL_MAP = [
		PacketeryPsr\Log\LogLevel::EMERGENCY => PacketeryTracy\ILogger::CRITICAL,
		PacketeryPsr\Log\LogLevel::ALERT => PacketeryTracy\ILogger::CRITICAL,
		PacketeryPsr\Log\LogLevel::CRITICAL => PacketeryTracy\ILogger::CRITICAL,
		PacketeryPsr\Log\LogLevel::ERROR => PacketeryTracy\ILogger::ERROR,
		PacketeryPsr\Log\LogLevel::WARNING => PacketeryTracy\ILogger::WARNING,
		PacketeryPsr\Log\LogLevel::NOTICE => PacketeryTracy\ILogger::WARNING,
		PacketeryPsr\Log\LogLevel::INFO => PacketeryTracy\ILogger::INFO,
		PacketeryPsr\Log\LogLevel::DEBUG => PacketeryTracy\ILogger::DEBUG,
	];

	/** @var PacketeryTracy\ILogger */
	private $tracyLogger;


	public function __construct(PacketeryTracy\ILogger $tracyLogger)
	{
		$this->tracyLogger = $tracyLogger;
	}


	public function log($level, $message, array $context = [])
	{
		$level = self::LEVEL_MAP[$level] ?? PacketeryTracy\ILogger::ERROR;

		if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
			$this->tracyLogger->log($context['exception'], $level);
			unset($context['exception']);
		}

		if ($context) {
			$message = [
				'message' => $message,
				'context' => $context,
			];
		}

		$this->tracyLogger->log($message, $level);
	}
}
