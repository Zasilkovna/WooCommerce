<?php

namespace Packetery\Phpro\SoapClient\CodeGenerator\Rules;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
/**
 * Interface RuleSetInterface
 *
 * @package Phpro\SoapClient\CodeGenerator\Rules
 * @internal
 */
interface RuleSetInterface
{
    /**
     * @param ContextInterface $context
     */
    public function applyRules(ContextInterface $context);
}
