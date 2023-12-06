<?php

namespace Packetery\spec\Phpro\SoapClient\Type;

use Packetery\Phpro\SoapClient\Type\MixedResult;
use Packetery\Phpro\SoapClient\Type\ResultInterface;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
/**
 * Class MixedResultSpec
 *
 * @package spec\Phpro\SoapClient\Type
 * @mixin MixedResult
 * @internal
 */
class MixedResultSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('actualResult');
    }
    function it_is_initializable()
    {
        $this->shouldHaveType(MixedResult::class);
    }
    function it_is_a_result()
    {
        $this->shouldImplement(ResultInterface::class);
    }
    function it_contains_the_mixed_result()
    {
        $this->getResult()->shouldBe('actualResult');
    }
}
