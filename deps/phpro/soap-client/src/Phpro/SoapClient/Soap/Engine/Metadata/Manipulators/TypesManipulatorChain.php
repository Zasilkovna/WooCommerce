<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Manipulators;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\TypeCollection;
/** @internal */
final class TypesManipulatorChain implements TypesManipulatorInterface
{
    /**
     * @var TypesManipulatorInterface[]
     */
    private $manipulators;
    public function __construct(TypesManipulatorInterface ...$manipulators)
    {
        $this->manipulators = $manipulators;
    }
    public function __invoke(TypeCollection $allTypes) : TypeCollection
    {
        return \array_reduce($this->manipulators, static function (TypeCollection $types, TypesManipulatorInterface $manipulator) : TypeCollection {
            return $manipulator($types);
        }, $allTypes);
    }
}
