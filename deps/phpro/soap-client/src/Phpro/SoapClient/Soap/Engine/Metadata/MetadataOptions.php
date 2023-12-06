<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Engine\Metadata;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Manipulators\MethodsManipulatorInterface;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Manipulators\MethodsManipulatorChain;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Manipulators\TypesManipulatorInterface;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Manipulators\TypesManipulatorChain;
/** @internal */
final class MetadataOptions
{
    /**
     * @var MethodsManipulatorInterface
     */
    private $methodsManipulator;
    /**
     * @var TypesManipulatorInterface
     */
    private $typesManipulator;
    public function __construct(MethodsManipulatorInterface $methodsManipulator, TypesManipulatorInterface $typesManipulator)
    {
        $this->methodsManipulator = $methodsManipulator;
        $this->typesManipulator = $typesManipulator;
    }
    public static function empty() : self
    {
        return new self(new MethodsManipulatorChain(), new TypesManipulatorChain());
    }
    public function withMethodsManipulator(MethodsManipulatorInterface $methodsManipulator) : self
    {
        $new = clone $this;
        $new->methodsManipulator = $methodsManipulator;
        return $new;
    }
    public function withTypesManipulator(TypesManipulatorInterface $typesManipulator) : self
    {
        $new = clone $this;
        $new->typesManipulator = $typesManipulator;
        return $new;
    }
    public function getMethodsManipulator() : MethodsManipulatorInterface
    {
        return $this->methodsManipulator;
    }
    public function getTypesManipulator() : TypesManipulatorInterface
    {
        return $this->typesManipulator;
    }
}
