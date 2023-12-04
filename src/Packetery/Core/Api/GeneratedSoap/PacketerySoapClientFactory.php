<?php

namespace Packetery\Core\Api\GeneratedSoap;

use Packetery\Core\Api\GeneratedSoap\PacketerySoapClient;
use Packetery\Core\Api\GeneratedSoap\PacketerySoapClassmap;
use Packetery\Symfony\Component\EventDispatcher\EventDispatcher;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapEngineFactory;
use Packetery\Phpro\SoapClient\Soap\Driver\ExtSoap\ExtSoapOptions;

class PacketerySoapClientFactory
{

    public static function factory(string $wsdl) : \Packetery\Core\Api\GeneratedSoap\PacketerySoapClient
    {
        $engine = ExtSoapEngineFactory::fromOptions(
            ExtSoapOptions::defaults($wsdl, [])
                ->withClassMap(PacketerySoapClassmap::getCollection())
        );
        $eventDispatcher = new EventDispatcher();

        return new PacketerySoapClient($engine, $eventDispatcher);
    }


}

