<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Engine\Metadata;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\MethodCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\TypeCollection;
/** @internal */
class LazyInMemoryMetadata implements MetadataInterface
{
    /**
     * @var MetadataInterface
     */
    private $metadata;
    /**
     * @var TypeCollection|null
     */
    private $types;
    /**
     * @var MethodCollection|null
     */
    private $methods;
    public function __construct(MetadataInterface $metadata)
    {
        $this->metadata = $metadata;
    }
    public function getTypes() : TypeCollection
    {
        if (!$this->types) {
            $this->types = $this->metadata->getTypes();
        }
        return $this->types;
    }
    public function getMethods() : MethodCollection
    {
        if (!$this->methods) {
            $this->methods = $this->metadata->getMethods();
        }
        return $this->methods;
    }
}
