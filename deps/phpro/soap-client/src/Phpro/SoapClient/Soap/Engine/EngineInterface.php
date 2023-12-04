<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Engine;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\MetadataProviderInterface;
use Packetery\Phpro\SoapClient\Soap\Handler\LastRequestInfoCollectorInterface;
/** @internal */
interface EngineInterface extends MetadataProviderInterface, LastRequestInfoCollectorInterface
{
    public function request(string $method, array $arguments);
}
