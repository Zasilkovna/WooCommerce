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
 * Tracy\ILogger to PacketeryPsr\Log\LoggerInterface adapter.
 */
class TracyToPacketeryPsrLoggerAdapter extends PacketeryPsr\Log\AbstractLogger
{
	/** PSR-3 log level to Tracy logger level mapping */
	private const LEVEL_MAP = [
		PacketeryPsr\Log\LogLevel::EMERGENCY => Tracy\ILogger::CRITICAL,
		PacketeryPsr\Log\LogLevel::ALERT => Tracy\ILogger::CRITICAL,
		PacketeryPsr\Log\LogLevel::CRITICAL => Tracy\ILogger::CRITICAL,
		PacketeryPsr\Log\LogLevel::ERROR => Tracy\ILogger::ERROR,
		PacketeryPsr\Log\LogLevel::WARNING => Tracy\ILogger::WARNING,
		PacketeryPsr\Log\LogLevel::NOTICE => Tracy\ILogger::WARNING,
		PacketeryPsr\Log\LogLevel::INFO => Tracy\ILogger::INFO,
		PacketeryPsr\Log\LogLevel::DEBUG => Tracy\ILogger::DEBUG,
	];

	/** @var Tracy\ILogger */
	private $tracyLogger;


	public function __construct(Tracy\ILogger $tracyLogger)
	{
		$this->tracyLogger = $tracyLogger;
	}


	public function log($level, $message, array $context = [])
	{
		$level = self::LEVEL_MAP[$level] ?? Tracy\ILogger::ERROR;

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
