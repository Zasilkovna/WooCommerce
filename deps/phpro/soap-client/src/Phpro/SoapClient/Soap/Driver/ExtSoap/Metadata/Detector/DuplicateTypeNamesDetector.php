<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\Metadata\Detector;

use Packetery\Phpro\SoapClient\CodeGenerator\Util\Normalizer;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Collection\TypeCollection;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Model\Type;
/** @internal */
final class DuplicateTypeNamesDetector
{
    /**
     * @param TypeCollection $types
     *
     * @return string[]
     */
    public function __invoke(TypeCollection $types) : array
    {
        return \array_keys(\array_filter(\array_count_values($types->map(static function (Type $type) : string {
            return Normalizer::normalizeClassname($type->getName());
        })), static function (int $count) : bool {
            return $count > 1;
        }));
    }
}
