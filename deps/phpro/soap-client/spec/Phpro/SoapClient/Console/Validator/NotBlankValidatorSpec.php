<?php

namespace Packetery\spec\Phpro\SoapClient\Console\Validator;

use Packetery\Phpro\SoapClient\Console\Validator\NotBlankValidator;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Symfony\Component\Console\Exception\LogicException;
/**
 * Class RequiredQuestionValidatorSpec
 * @package spec\Phpro\SoapClient\Event
 * @internal
 */
class NotBlankValidatorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(NotBlankValidator::class);
    }
    function it_should_validate_not_blank()
    {
        $this->__invoke('test')->shouldBe('test');
        $this->shouldThrow(LogicException::class)->during('__invoke', [null]);
        $this->shouldThrow(LogicException::class)->during('__invoke', ['']);
    }
}
