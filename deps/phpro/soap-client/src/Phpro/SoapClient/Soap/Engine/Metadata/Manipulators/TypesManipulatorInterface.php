<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Manipulators;

use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\TypeCollection;
/** @internal */
interface TypesManipulatorInterface
{
    /**
     * By implementing this method, you can change a collection of types into a different collection of types.
     * This makes it possible to alter, remove, combine, add, .. types on the fly!
     */
    public function __invoke(TypeCollection $allTypes) : TypeCollection;
}
