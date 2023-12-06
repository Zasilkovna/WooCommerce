<?php

namespace Packetery\Phpro\SoapClient\CodeGenerator\Context;

use Packetery\Phpro\SoapClient\CodeGenerator\Model\Type;
use Packetery\Laminas\Code\Generator\ClassGenerator;
/**
 * Class ClientContext
 *
 * @package Phpro\SoapClient\CodeGenerator\Context
 * @internal
 */
class ClientContext implements ContextInterface
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $namespace;
    /**
     * PropertyContext constructor.
     *
     * @param string $name
     * @param string $namespace
     */
    public function __construct(string $name, string $namespace)
    {
        $this->name = $name;
        $this->namespace = $namespace;
    }
    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }
    /**
     * @return string
     */
    public function getNamespace() : string
    {
        return $this->namespace;
    }
    /**
     * @return string
     */
    public function getFqcn() : string
    {
        return $this->namespace . '\\' . $this->name;
    }
}
