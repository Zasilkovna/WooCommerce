<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator;

use Packetery\Phpro\SoapClient\CodeGenerator\GeneratorInterface;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Phpro\SoapClient\CodeGenerator\ConfigGenerator;
/**
 * Class ConfigGeneratorSpec
 * @internal
 */
class ConfigGeneratorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(ConfigGenerator::class);
    }
    function it_is_a_generator()
    {
        $this->shouldImplement(GeneratorInterface::class);
    }
}
