<?php

namespace Packetery\Phpro\SoapClient\CodeGenerator\Assembler;

use Packetery\Phpro\SoapClient\CodeGenerator\Context\ContextInterface;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\TypeContext;
use Packetery\Phpro\SoapClient\Exception\AssemblerException;
use Packetery\Phpro\SoapClient\Type\RequestInterface;
/**
 * Class RequestAssembler
 *
 * @package Phpro\SoapClient\CodeGenerator\Assembler
 * @internal
 */
class RequestAssembler implements AssemblerInterface
{
    /**
     * {@inheritdoc}
     */
    public function canAssemble(ContextInterface $context) : bool
    {
        return $context instanceof TypeContext;
    }
    /**
     * @param ContextInterface|TypeContext $context
     *
     * @throws AssemblerException
     */
    public function assemble(ContextInterface $context)
    {
        try {
            $interfaceAssembler = new InterfaceAssembler(RequestInterface::class);
            if ($interfaceAssembler->canAssemble($context)) {
                $interfaceAssembler->assemble($context);
            }
        } catch (\Exception $e) {
            throw AssemblerException::fromException($e);
        }
    }
}
