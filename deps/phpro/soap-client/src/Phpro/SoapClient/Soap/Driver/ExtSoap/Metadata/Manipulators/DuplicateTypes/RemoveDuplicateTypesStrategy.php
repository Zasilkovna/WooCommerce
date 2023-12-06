<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Metadata\Manipulators\DuplicateTypes;

use Packetery\Phpro\SoapClient\CodeGenerator\Util\Normalizer;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Metadata\Detector\DuplicateTypeNamesDetector;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\TypeCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Manipulators\TypesManipulatorInterface;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Type;
/** @internal */
final class RemoveDuplicateTypesStrategy implements TypesManipulatorInterface
{
    public function __invoke(TypeCollection $types) : TypeCollection
    {
        $duplicateNames = (new DuplicateTypeNamesDetector())($types);
        return $types->filter(static function (Type $type) use($duplicateNames) : bool {
            return !\in_array(Normalizer::normalizeClassname($type->getName()), $duplicateNames, \true);
        });
    }
}
