<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Engine\Metadata;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\MethodCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\TypeCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Manipulators\MethodsManipulatorInterface;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Manipulators\TypesManipulatorInterface;
/** @internal */
class ManipulatedMetadata implements MetadataInterface
{
    /**
     * @var MetadataInterface
     */
    private $metadata;
    /**
     * @var TypesManipulatorInterface
     */
    private $typesChangingStrategy;
    /**
     * @var MethodsManipulatorInterface
     */
    private $methodsChangingStrategyInterface;
    public function __construct(MetadataInterface $metadata, MethodsManipulatorInterface $methodsChangingStrategyInterface, TypesManipulatorInterface $typesChangingStrategy)
    {
        $this->metadata = $metadata;
        $this->methodsChangingStrategyInterface = $methodsChangingStrategyInterface;
        $this->typesChangingStrategy = $typesChangingStrategy;
    }
    public function getTypes() : TypeCollection
    {
        return ($this->typesChangingStrategy)($this->metadata->getTypes());
    }
    public function getMethods() : MethodCollection
    {
        return ($this->methodsChangingStrategyInterface)($this->metadata->getMethods());
    }
}
