<?php

namespace Packetery\Phpro\SoapClient\CodeGenerator;

use Packetery\Laminas\Code\Generator\Exception\ClassNotFoundException;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClientMethodContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\PropertyContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Client;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Type;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules\RuleSetInterface;
use Packetery\Laminas\Code\Generator\ClassGenerator;
use Packetery\Laminas\Code\Generator\FileGenerator;
/**
 * Class ClientGenerator
 *
 * @package Phpro\SoapClient\CodeGenerator
 * @internal
 */
class ClientGenerator implements GeneratorInterface
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
     * @param Client        $client
     *
     * @return string
     */
    public function generate(FileGenerator $file, $client) : string
    {
        try {
            // @phpstan-ignore-next-line
            $class = $file->getClass() ?: new ClassGenerator();
        } catch (ClassNotFoundException $exception) {
            $class = new ClassGenerator();
        }
        $class->setNamespaceName($client->getNamespace());
        $class->setName($client->getName());
        $methods = $client->getMethodMap();
        foreach ($methods->getMethods() as $method) {
            $this->ruleSet->applyRules(new ClientMethodContext($class, $method));
        }
        $file->setClass($class);
        return $file->generate();
    }
}
