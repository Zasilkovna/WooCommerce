<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Detector;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\MethodCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Method;
/** @internal */
final class ResponseTypesDetector
{
    public function __invoke(MethodCollection $methods) : array
    {
        return \array_unique(\array_map(static function (Method $method) : string {
            return $method->getReturnType()->getName();
        }, \iterator_to_array($methods)));
    }
}
