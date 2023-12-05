<?php

use Packetery\Phpro\SoapClient\CodeGenerator\Assembler;
use Packetery\Phpro\SoapClient\CodeGenerator\Rules;
use Packetery\Phpro\SoapClient\CodeGenerator\Config\Config;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapEngineFactory;

return Config::create()
    ->setEngine($engine = ExtSoapEngineFactory::fromOptions(
        ExtSoapOptions::defaults('config/packetery-soap-bugfix.wsdl', [])
            ->disableWsdlCache()
    ))
    ->setTypeDestination('src/Packetery/Core/Api/GeneratedSoap/Type')
    ->setTypeNamespace('Packetery\Core\Api\GeneratedSoap\Type')
    ->setClientDestination('src/Packetery/Core/Api/GeneratedSoap')
    ->setClientName('PacketerySoapClient')
    ->setClientNamespace('Packetery\Core\Api\GeneratedSoap')
    ->setClassMapDestination('src/Packetery/Core/Api/GeneratedSoap')
    ->setClassMapName('PacketerySoapClassmap')
    ->setClassMapNamespace('Packetery\Core\Api\GeneratedSoap')
    ->addRule(new Rules\AssembleRule(new Assembler\GetterAssembler(
        (new Assembler\GetterAssemblerOptions())->withDocBlocks(false)
    )))
    ->addRule(new Rules\AssembleRule(new Assembler\ImmutableSetterAssembler(
        (new Assembler\ImmutableSetterAssemblerOptions())->withDocBlocks(false)
    )))
    ->addRule(
        new Rules\IsRequestRule(
            $engine->getMetadata(),
            new Rules\MultiRule([
                new Rules\AssembleRule(new Assembler\RequestAssembler()),
                new Rules\AssembleRule(new Assembler\ConstructorAssembler(
                    (new Assembler\ConstructorAssemblerOptions())->withDocBlocks(false)
                )),
            ])
        )
    )
    ->addRule(
        new Rules\IsResultRule(
            $engine->getMetadata(),
            new Rules\MultiRule([
                new Rules\AssembleRule(new Assembler\ResultAssembler()),
            ])
        )
    )
;
