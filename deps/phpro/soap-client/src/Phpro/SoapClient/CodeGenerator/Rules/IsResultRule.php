<?php

declare (strict_types=1);
namespace Packetery\Phpro\SoapClient\CodeGenerator\Rules;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Util\Normalizer;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\Detector\ResponseTypesDetector;
use Packetery\Phpro\SoapClient\Soap\Engine\Metadata\MetadataInterface;
/** @internal */
class IsResultRule implements RuleInterface
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
    private $responseTypes;
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
        if (!\in_array($type->getName(), $this->listResponseTypes(), \true)) {
            return \false;
        }
        return $this->subRule->appliesToContext($context);
    }
    public function apply(ContextInterface $context)
    {
        $this->subRule->apply($context);
    }
    private function listResponseTypes() : array
    {
        if (null === $this->responseTypes) {
            $this->responseTypes = \array_map(static function (string $type) {
                return Normalizer::normalizeClassname($type);
            }, (new ResponseTypesDetector())($this->metadata->getMethods()));
        }
        return $this->responseTypes;
    }
}
