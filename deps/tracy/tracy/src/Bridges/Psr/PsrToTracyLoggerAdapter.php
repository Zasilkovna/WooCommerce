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
 * Psr\Log\LoggerInterface to \Packetery\Tracy\ILogger adapter.
 * @internal
 */
class PsrToTracyLoggerAdapter implements \Packetery\Tracy\ILogger
{
    /** Tracy logger level to PSR-3 log level mapping */
    private const LevelMap = [\Packetery\Tracy\ILogger::DEBUG => Psr\Log\LogLevel::DEBUG, \Packetery\Tracy\ILogger::INFO => Psr\Log\LogLevel::INFO, \Packetery\Tracy\ILogger::WARNING => Psr\Log\LogLevel::WARNING, \Packetery\Tracy\ILogger::ERROR => Psr\Log\LogLevel::ERROR, \Packetery\Tracy\ILogger::EXCEPTION => Psr\Log\LogLevel::ERROR, \Packetery\Tracy\ILogger::CRITICAL => Psr\Log\LogLevel::CRITICAL];
    /** @var Psr\Log\LoggerInterface */
    private $psrLogger;
    public function __construct(Psr\Log\LoggerInterface $psrLogger)
    {
        $this->psrLogger = $psrLogger;
    }
    public function log($value, $level = self::INFO)
    {
        if ($value instanceof \Throwable) {
            $message = \Packetery\Tracy\Helpers::getClass($value) . ': ' . $value->getMessage() . ($value->getCode() ? ' #' . $value->getCode() : '') . ' in ' . $value->getFile() . ':' . $value->getLine();
            $context = ['exception' => $value];
        } elseif (!\is_string($value)) {
            $message = \trim(\Packetery\Tracy\Dumper::toText($value));
            $context = [];
        } else {
            $message = $value;
            $context = [];
        }
        $this->psrLogger->log(self::LevelMap[$level] ?? Psr\Log\LogLevel::ERROR, $message, $context);
    }
}
