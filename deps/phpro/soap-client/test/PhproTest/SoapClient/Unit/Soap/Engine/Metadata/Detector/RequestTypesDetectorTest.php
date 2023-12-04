<?php

declare (strict_types=1);
namespace Packetery\PhproTest\SoapClient\Unit\Soap\Engine\Metadata\Detector;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\MethodCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Detector\RequestTypesDetector;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Method;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Parameter;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\XsdType;
use Packetery\PHPUnit\Framework\TestCase;
/** @internal */
class RequestTypesDetectorTest extends TestCase
{
    /** @test */
    public function it_can_detect_request_types() : void
    {
        $methods = new MethodCollection(new Method('method1', [], XsdType::create('string')), new Method('method2', [new Parameter('param1', XsdType::create('RequestType1'))], XsdType::create('string')), new Method('method3', [new Parameter('param1', XsdType::create('RequestType2')), new Parameter('param2', XsdType::create('RequestType3'))], XsdType::create('string')), new Method('method4', [new Parameter('param1', XsdType::create('RequestType4'))], XsdType::create('string')), new Method('method5', [new Parameter('param1', XsdType::create('string'))], XsdType::create('string')));
        $detected = (new RequestTypesDetector())($methods);
        self::assertSame(['RequestType1', 'RequestType4', 'string'], $detected);
    }
}
