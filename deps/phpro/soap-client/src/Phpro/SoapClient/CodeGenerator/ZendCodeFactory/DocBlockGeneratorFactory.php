<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\CodeGenerator\ZendCodeFactory;

use Packetery\Laminas\Code\Generator\DocBlockGenerator;
/**
 * @deprecated Please use LaminasCodeFactory\DocBlockGeneratorFactory instead
 * @see \Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory
 * @internal
 */
final class DocBlockGeneratorFactory
{
    public static function fromArray(array $data) : DocBlockGenerator
    {
        $generator = DocBlockGenerator::fromArray($data);
        $generator->setWordWrap(\false);
        return $generator;
    }
}
