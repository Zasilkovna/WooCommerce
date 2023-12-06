<?php

namespace Packetery\spec\Phpro\SoapClient\CodeGenerator;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClientFactoryContext;
use Packetery\Phpro\SoapClient\CodeGenerator\GeneratorInterface;
use Packetery\PhpSpec\ObjectBehavior;
use Packetery\Prophecy\Argument;
use Packetery\Phpro\SoapClient\CodeGenerator\ClientFactoryGenerator;
use Packetery\Laminas\Code\Generator\FileGenerator;
/**
 * Class ClientFactoryGeneratorSpec
 * @internal
 */
class ClientFactoryGeneratorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(ClientFactoryGenerator::class);
    }
    function it_is_a_generator()
    {
        $this->shouldImplement(GeneratorInterface::class);
    }
}
