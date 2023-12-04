<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Metadata\Visitor;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\XsdType;
/** @internal */
class SimpleTypeVisitor implements XsdTypeVisitorInterface
{
    public function __invoke(string $soapType) : ?XsdType
    {
        if (!\preg_match('/^(?!list|union|struct)(?P<baseType>\\w+) (?P<typeName>\\w+)/', $soapType, $matches)) {
            return null;
        }
        return XsdType::create($matches['typeName'])->withBaseType($matches['baseType']);
    }
}
