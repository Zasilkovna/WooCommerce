<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Engine\Metadata;

/** @internal */
interface MetadataProviderInterface
{
    public function getMetadata() : MetadataInterface;
}
