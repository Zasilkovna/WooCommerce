<?php

declare(strict_types=1);

namespace PHPSTORM_META;

expectedArguments(\PacketeryTracy\Debugger::log(), 1, \PacketeryTracy\ILogger::DEBUG, \PacketeryTracy\ILogger::INFO, \PacketeryTracy\ILogger::WARNING, \PacketeryTracy\ILogger::ERROR, \PacketeryTracy\ILogger::EXCEPTION, \PacketeryTracy\ILogger::CRITICAL);
expectedArguments(\PacketeryTracy\ILogger::log(), 1, \PacketeryTracy\ILogger::DEBUG, \PacketeryTracy\ILogger::INFO, \PacketeryTracy\ILogger::WARNING, \PacketeryTracy\ILogger::ERROR, \PacketeryTracy\ILogger::EXCEPTION, \PacketeryTracy\ILogger::CRITICAL);

exitPoint(\dumpe());
