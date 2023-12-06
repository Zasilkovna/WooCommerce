<?php

namespace Packetery\Phpro\SoapClient\CodeGenerator\Assembler;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\PropertyContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Util\Normalizer;
use Packetery\Phpro\SoapClient\CodeGenerator\LaminasCodeFactory\DocBlockGeneratorFactory;
use Packetery\Phpro\SoapClient\Exception\AssemblerException;
use Packetery\Laminas\Code\Generator\MethodGenerator;
/**
 * Class ImmutableSetterAssembler
 *
 * @package Phpro\SoapClient\CodeGenerator\Assembler
 * @internal
 */
class ImmutableSetterAssembler implements AssemblerInterface
{
    /**
     * @var ImmutableSetterAssemblerOptions
     */
    private $options;
    /**
     * ImmutableSetterAssembler constructor.
     *
     * @param ImmutableSetterAssemblerOptions|null $options
     */
    public function __construct(ImmutableSetterAssemblerOptions $options = null)
    {
        $this->options = $options ?? new ImmutableSetterAssemblerOptions();
    }
    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function canAssemble(ContextInterface $context) : bool
    {
        return $context instanceof PropertyContext;
    }
    /**
     * Assembles pieces of code.
     *
     * @param ContextInterface|PropertyContext $context
     *
     * @throws AssemblerException
     */
    public function assemble(ContextInterface $context)
    {
        $class = $context->getClass();
        $property = $context->getProperty();
        try {
            $methodName = Normalizer::generatePropertyMethod('with', $property->getName());
            $class->removeMethod($methodName);
            $lines = [\sprintf('$new = clone $this;'), \sprintf('$new->%1$s = $%1$s;', $property->getName()), '', \sprintf('return $new;')];
            $parameterOptions = ['name' => $property->getName()];
            if ($this->options->useTypeHints()) {
                $parameterOptions['type'] = $property->getType();
            }
            $methodGenerator = new MethodGenerator($methodName);
            $methodGenerator->setParameters([$parameterOptions]);
            $methodGenerator->setBody(\implode($class::LINE_FEED, $lines));
            if ($this->options->useReturnTypes()) {
                $methodGenerator->setReturnType($class->getNamespaceName() . '\\' . $class->getName());
            }
            if ($this->options->useDocBlocks()) {
                $methodGenerator->setDocBlock(DocBlockGeneratorFactory::fromArray(['tags' => [['name' => 'param', 'description' => \sprintf('%s $%s', $property->getType(), $property->getName())], ['name' => 'return', 'description' => $class->getName()]]]));
            }
            $class->addMethodFromGenerator($methodGenerator);
        } catch (\Exception $e) {
            throw AssemblerException::fromException($e);
        }
    }
}
