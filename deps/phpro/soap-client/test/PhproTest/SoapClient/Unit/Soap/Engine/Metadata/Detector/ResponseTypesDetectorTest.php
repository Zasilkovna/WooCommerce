<?php

declare (strict_types=1);
namespace Packetery\PhproTest\SoapClient\Unit\Soap\Engine\Metadata\Detector;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\MethodCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Detector\RequestTypesDetector;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Detector\ResponseTypesDetector;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Method;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Parameter;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\XsdType;
use Packetery\PHPUnit\Framework\TestCase;
/** @internal */
class ResponseTypesDetectorTest extends TestCase
{
    /** @test */
    public function it_can_detect_request_types() : void
    {
        $methods = new MethodCollection(new Method('method1', [], XsdType::create('Response1')), new Method('method3', [new Parameter('param1', XsdType::create('RequestType2')), new Parameter('param2', XsdType::create('RequestType3'))], XsdType::create('Response2')), new Method('method1', [], XsdType::create('string')));
        $detected = (new ResponseTypesDetector())($methods);
        self::assertSame(['Response1', 'Response2', 'string'], $detected);
    }
}
