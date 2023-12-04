<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Engine;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\MetadataProviderInterface;
/** @internal */
interface DriverInterface extends EncoderInterface, DecoderInterface, MetadataProviderInterface
{
}
