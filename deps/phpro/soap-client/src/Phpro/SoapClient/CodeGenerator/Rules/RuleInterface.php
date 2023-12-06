<?php

namespace Packetery\Phpro\SoapClient\CodeGenerator\Rules;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
/**
 * Interface RuleInterface
 *
 * @package Phpro\SoapClient\CodeGenerator\Rules
 * @internal
 */
interface RuleInterface
{
    /**
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function appliesToContext(ContextInterface $context) : bool;
    /**
     * @param ContextInterface $context
     */
    public function apply(ContextInterface $context);
}
