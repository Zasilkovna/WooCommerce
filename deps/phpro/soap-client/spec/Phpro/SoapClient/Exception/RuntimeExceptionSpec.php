<?php

namespace Packetery\spec\Phpro\SoapClient\Exception;

use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
/** @internal */
class RuntimeExceptionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Packetery\\Phpro\\SoapClient\\Exception\\RuntimeException');
    }
    function it_should_be_an_exception()
    {
        $this->shouldHaveType(\RuntimeException::class);
    }
}
