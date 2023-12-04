<?php

namespace Packetery\Phpro\SoapClient\CodeGenerator\Context;

use Packetery\Phpro\SoapClient\CodeGenerator\Model\ClientMethod;
use Packetery\Laminas\Code\Generator\ClassGenerator;
/** @internal */
class ClientMethodContext implements ContextInterface
{
    /**
     * @var ClassGenerator
     */
    private $class;
    /**
     * @var ClientMethod
     */
    private $method;
    /**
     * PropertyContext constructor.
     *
     * @param ClassGenerator $class
     * @param ClientMethod $method
     */
    public function __construct(ClassGenerator $class, ClientMethod $method)
    {
        $this->class = $class;
        $this->method = $method;
    }
    /**
     * @return ClassGenerator
     */
    public function getClass() : ClassGenerator
    {
        return $this->class;
    }
    /**
     * @return ClientMethod
     */
    public function getMethod() : ClientMethod
    {
        return $this->method;
    }
    /**
     * @return int
     */
    public function getArgumentCount() : int
    {
        return \count($this->method->getParameters());
    }
}
