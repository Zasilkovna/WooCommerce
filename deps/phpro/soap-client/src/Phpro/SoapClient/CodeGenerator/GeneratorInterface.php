<?php

namespace Packetery\Phpro\SoapClient\CodeGenerator;

use Packetery\Laminas\Code\Generator\FileGenerator;
/**
 * Interface GeneratorInterface
 *
 * @package Phpro\SoapClient\CodeGenerator
 * @internal
 */
interface GeneratorInterface
{
    // to ease X-OS compat, always use linux newlines
    const EOL = "\n";
    /**
     * @param FileGenerator $file
     * @param mixed         $model
     *
     * @return string
     */
    public function generate(FileGenerator $file, $model) : string;
}
