<?php

namespace Packetery\spec\Phpro\SoapClient\Type;

use Packetery\Phpro\SoapClient\Type\MultiArgumentRequest;
use Packetery\Phpro\SoapClient\Type\MultiArgumentRequestInterface;
use Packetery\Phpro\SoapClient\Type\RequestInterface;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
/**
 * Class MultiArgumentRequestSpec
 *
 * @package spec\Phpro\SoapClient\Type
 * @mixin MultiArgumentRequest
 * @internal
 */
class MultiArgumentRequestSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(['arg1', 'arg2']);
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(MultiArgumentRequest::class);
    }
    function it_is_a_multiple_argument_request()
    {
        $this->shouldImplement(MultiArgumentRequestInterface::class);
        $this->shouldImplement(RequestInterface::class);
    }
    function it_has_multiple_arguments()
    {
        $this->getArguments()->shouldBe(['arg1', 'arg2']);
    }
}
