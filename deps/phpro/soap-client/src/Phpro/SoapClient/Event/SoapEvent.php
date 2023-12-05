<?php

namespace Packetery\Phpro\SoapClient\Event;

use Packetery\Symfony\Component\EventDispatcher\Event as LegacyEvent;
use Packetery\Symfony\Contracts\EventDispatcher\Event as ContractEvent;
/**
 * For backward compatibility with Symfony 4
 * We'll make this event PSR14 in the future so that we don't rely on the symfony event anymore.
 * TODO : Replace internal event subscribers by listeners
 */
// @codingStandardsIgnoreStart
if (\class_exists(ContractEvent::class)) {
    /** @internal */
    class SoapEvent extends ContractEvent
    {
    }
} else {
    /** @internal */
    class SoapEvent extends LegacyEvent
    {
    }
}
// @codingStandardsIgnoreEnd
