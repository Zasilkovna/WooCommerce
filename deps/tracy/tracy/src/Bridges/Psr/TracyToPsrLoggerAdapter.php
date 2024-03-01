<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Tracy\Bridges\Psr;

use Packetery\Psr;
use Packetery\Tracy;
/**
 * \Packetery\Tracy\ILogger to Psr\Log\LoggerInterface adapter.
 * @internal
 */
class TracyToPsrLoggerAdapter extends Psr\Log\AbstractLogger
{
    /** PSR-3 log level to Tracy logger level mapping */
    private const LevelMap = [Psr\Log\LogLevel::EMERGENCY => \Packetery\Tracy\ILogger::CRITICAL, Psr\Log\LogLevel::ALERT => \Packetery\Tracy\ILogger::CRITICAL, Psr\Log\LogLevel::CRITICAL => \Packetery\Tracy\ILogger::CRITICAL, Psr\Log\LogLevel::ERROR => \Packetery\Tracy\ILogger::ERROR, Psr\Log\LogLevel::WARNING => \Packetery\Tracy\ILogger::WARNING, Psr\Log\LogLevel::NOTICE => \Packetery\Tracy\ILogger::WARNING, Psr\Log\LogLevel::INFO => \Packetery\Tracy\ILogger::INFO, Psr\Log\LogLevel::DEBUG => \Packetery\Tracy\ILogger::DEBUG];
    /** @var \Packetery\Tracy\ILogger */
    private $tracyLogger;
    public function __construct(\Packetery\Tracy\ILogger $tracyLogger)
    {
        $this->tracyLogger = $tracyLogger;
    }
    public function log($level, $message, array $context = []) : void
    {
        $level = self::LevelMap[$level] ?? \Packetery\Tracy\ILogger::ERROR;
        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $this->tracyLogger->log($context['exception'], $level);
            unset($context['exception']);
        }
        if ($context) {
            $message = ['message' => $message, 'context' => $context];
        }
        $this->tracyLogger->log($message, $level);
    }
}
