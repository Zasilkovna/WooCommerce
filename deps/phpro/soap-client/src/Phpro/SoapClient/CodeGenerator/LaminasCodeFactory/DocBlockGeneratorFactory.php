<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\CodeGenerator\LaminasCodeFactory;

use Packetery\Laminas\Code\Generator\DocBlockGenerator;
/** @internal */
final class DocBlockGeneratorFactory
{
    public static function fromArray(array $data) : DocBlockGenerator
    {
        $generator = DocBlockGenerator::fromArray($data);
        $generator->setWordWrap(\false);
        return $generator;
    }
}
