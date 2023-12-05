<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\CodeGenerator\Rules;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Util\Normalizer;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Detector\RequestTypesDetector;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\MetadataInterface;
/** @internal */
class IsRequestRule implements RuleInterface
{
    /**
     * @var MetadataInterface
     */
    private $metadata;
    /**
     * @var RuleInterface
     */
    private $subRule;
    /**
     * @var array|null
     */
    private $requestTypes;
    public function __construct(MetadataInterface $metadata, RuleInterface $subRule)
    {
        $this->metadata = $metadata;
        $this->subRule = $subRule;
    }
    public function appliesToContext(ContextInterface $context) : bool
    {
        if (!$context instanceof TypeContext) {
            return \false;
        }
        $type = $context->getType();
        if (!\in_array($type->getName(), $this->listRequestTypes(), \true)) {
            return \false;
        }
        return $this->subRule->appliesToContext($context);
    }
    public function apply(ContextInterface $context)
    {
        $this->subRule->apply($context);
    }
    private function listRequestTypes() : array
    {
        if (null === $this->requestTypes) {
            $this->requestTypes = \array_map(static function (string $type) {
                return Normalizer::normalizeClassname($type);
            }, (new RequestTypesDetector())($this->metadata->getMethods()));
        }
        return $this->requestTypes;
    }
}
