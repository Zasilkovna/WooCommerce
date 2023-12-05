<?php

namespace Packetery\Phpro\SoapClient\Console\Helper;

use Packetery\Phpro\SoapClient\CodeGenerator\Config\Config;
use Packetery\Phpro\SoapClient\CodeGenerator\Config\ConfigInterface;
use Packetery\Phpro\SoapClient\Exception\InvalidArgumentException;
use Packetery\Phpro\SoapClient\Util\Filesystem;
use Packetery\Symfony\Component\Console\Helper\Helper;
use Packetery\Symfony\Component\Console\Input\InputInterface;
/** @internal */
class ConfigHelper extends Helper
{
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * ConfigHelper constructor.
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }
    public function getName() : string
    {
        return 'config';
    }
    /**
     * Attempts to load the configuration file, returns it on success
     * @param InputInterface $input
     * @return ConfigInterface
     */
    public function load(InputInterface $input) : ConfigInterface
    {
        $configFile = $input->getOption('config');
        if (!$configFile || !$this->filesystem->fileExists($configFile)) {
            throw InvalidArgumentException::invalidConfigFile();
        }
        $config = (include $configFile);
        if (!$config instanceof ConfigInterface) {
            throw InvalidArgumentException::invalidConfigFile();
        }
        return $config;
    }
}
