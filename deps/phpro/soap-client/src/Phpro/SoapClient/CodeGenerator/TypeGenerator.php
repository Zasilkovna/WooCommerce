<?php

namespace Packetery\Phpro\SoapClient\CodeGenerator;

use Packetery\Laminas\Code\Generator\Exception\ClassNotFoundException;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\PropertyContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Type;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules\RuleSetInterface;
use Packetery\Laminas\Code\Generator\ClassGenerator;
use Packetery\Laminas\Code\Generator\FileGenerator;
/**
 * Class TypeGenerator
 *
 * @package Phpro\SoapClient\CodeGenerator
 * @internal
 */
class TypeGenerator implements GeneratorInterface
{
    /**
     * @var RuleSetInterface
     */
    private $ruleSet;
    /**
     * TypeGenerator constructor.
     *
     * @param RuleSetInterface $ruleSet
     */
    public function __construct(RuleSetInterface $ruleSet)
    {
        $this->ruleSet = $ruleSet;
    }
    /**
     * @param FileGenerator $file
     * @param Type          $type
     *
     * @return string
     */
    public function generate(FileGenerator $file, $type) : string
    {
        try {
            // @phpstan-ignore-next-line
            $class = $file->getClass() ?: new ClassGenerator();
        } catch (ClassNotFoundException $exception) {
            $class = new ClassGenerator();
        }
        $class->setNamespaceName($type->getNamespace());
        $class->setName($type->getName());
        $this->ruleSet->applyRules(new TypeContext($class, $type));
        foreach ($type->getProperties() as $property) {
            $this->ruleSet->applyRules(new PropertyContext($class, $type, $property));
        }
        $file->setClass($class);
        return $file->generate();
    }
}
