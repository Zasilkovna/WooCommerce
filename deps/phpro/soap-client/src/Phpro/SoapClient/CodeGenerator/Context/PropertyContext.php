<?php

namespace Packetery\Phpro\SoapClient\CodeGenerator\Context;

use Packetery\Phpro\SoapClient\CodeGenerator\Model\Property;
use Packetery\Phpro\SoapClient\CodeGenerator\Model\Type;
use Packetery\Laminas\Code\Generator\ClassGenerator;
/**
 * Class PropertyContext
 *
 * @package Phpro\SoapClient\CodeGenerator\Context
 * @internal
 */
class PropertyContext implements ContextInterface
{
    /**
     * @var ClassGenerator
     */
    private $class;
    /**
     * @var Type
     */
    private $type;
    /**
     * @var Property
     */
    private $property;
    /**
     * PropertyContext constructor.
     *
     * @param ClassGenerator $class
     * @param Type           $type
     * @param Property       $property
     */
    public function __construct(ClassGenerator $class, Type $type, Property $property)
    {
        $this->class = $class;
        $this->type = $type;
        $this->property = $property;
    }
    /**
     * @return ClassGenerator
     */
    public function getClass() : ClassGenerator
    {
        return $this->class;
    }
    /**
     * @return Type
     */
    public function getType() : Type
    {
        return $this->type;
    }
    /**
     * @return Property
     */
    public function getProperty() : Property
    {
        return $this->property;
    }
}
