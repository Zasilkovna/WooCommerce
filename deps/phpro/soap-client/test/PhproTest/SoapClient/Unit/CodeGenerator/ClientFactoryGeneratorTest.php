<?php

namespace Packetery\PhproTest\SoapClient\Unit\CodeGenerator;

use Packetery\Phpro\SoapClient\CodeGenerator\ClientFactoryGenerator;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClassMapContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClientContext;
use Packetery\Phpro\SoapClient\CodeGenerator\Context\ClientFactoryContext;
use Packetery\PHPUnit\Framework\TestCase;
use Packetery\Laminas\Code\Generator\FileGenerator;
/** @internal */
class ClientFactoryGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $expected = <<<BODY
<?php

namespace App\\Client;

use App\\Client\\Myclient;
use App\\Classmap\\SomeClassmap;
use Symfony\\Component\\EventDispatcher\\EventDispatcher;
use Phpro\\SoapClient\\Soap\\Driver\\ExtSoap\\ExtSoapEngineFactory;
use Phpro\\SoapClient\\Soap\\Driver\\ExtSoap\\ExtSoapOptions;

class MyclientFactory
{

    public static function factory(string \$wsdl) : \\App\\Client\\Myclient
    {
        \$engine = ExtSoapEngineFactory::fromOptions(
            ExtSoapOptions::defaults(\$wsdl, [])
                ->withClassMap(SomeClassmap::getCollection())
        );
        \$eventDispatcher = new EventDispatcher();

        return new Myclient(\$engine, \$eventDispatcher);
    }


}


BODY;
        $clientContext = new ClientContext('Myclient', 'Packetery\\App\\Client');
        $classMapContext = new ClassMapContext(new FileGenerator(), new \Packetery\Phpro\SoapClient\CodeGenerator\Model\TypeMap('Packetery\\App\\Types', []), 'SomeClassmap', 'Packetery\\App\\Classmap');
        $context = new ClientFactoryContext($clientContext, $classMapContext);
        $generator = new ClientFactoryGenerator();
        self::assertEquals($expected, $generator->generate(new FileGenerator(), $context));
    }
}
