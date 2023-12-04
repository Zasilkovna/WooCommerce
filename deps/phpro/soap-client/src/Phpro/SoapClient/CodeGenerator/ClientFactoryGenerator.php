<?php

namespace Packetery\Phpro\SoapClient\CodeGenerator;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClientFactoryContext;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapEngineFactory;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Packetery\Symfony\Component\EventDispatcher\EventDispatcher;
use Packetery\Laminas\Code\Generator\ClassGenerator;
use Packetery\Laminas\Code\Generator\FileGenerator;
use Packetery\Laminas\Code\Generator\MethodGenerator;
/**
 * Class ClientBuilderGenerator
 *
 * @package Phpro\SoapClient\CodeGenerator
 * @internal
 */
class ClientFactoryGenerator implements GeneratorInterface
{
    const BODY = <<<BODY
\$engine = ExtSoapEngineFactory::fromOptions(
    ExtSoapOptions::defaults(\$wsdl, [])
        ->withClassMap(%2\$s::getCollection())
);
\$eventDispatcher = new EventDispatcher();

return new %1\$s(\$engine, \$eventDispatcher);

BODY;
    /**
     * @param FileGenerator $file
     * @param ClientFactoryContext $context
     * @return string
     */
    public function generate(FileGenerator $file, $context) : string
    {
        $class = new ClassGenerator($context->getClientName() . 'Factory');
        $class->setNamespaceName($context->getClientNamespace());
        $class->addUse($context->getClientFqcn());
        $class->addUse($context->getClassmapFqcn());
        $class->addUse(EventDispatcher::class);
        $class->addUse(ExtSoapEngineFactory::class);
        $class->addUse(ExtSoapOptions::class);
        $class->addMethodFromGenerator(MethodGenerator::fromArray(['name' => 'factory', 'static' => \true, 'body' => \sprintf(self::BODY, $context->getClientName(), $context->getClassmapName()), 'returntype' => $context->getClientFqcn(), 'parameters' => [['name' => 'wsdl', 'type' => 'string']]]));
        $file->setClass($class);
        return $file->generate();
    }
}
