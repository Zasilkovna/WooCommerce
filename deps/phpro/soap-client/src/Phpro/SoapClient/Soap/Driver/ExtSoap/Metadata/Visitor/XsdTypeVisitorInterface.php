<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Metadata\Visitor;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\XsdType;
/** @internal */
interface XsdTypeVisitorInterface
{
    public function __invoke(string $soapType) : ?XsdType;
}
