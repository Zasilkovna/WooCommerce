<?php

declare(strict_types=1);

namespace Packetery\Checkout\Ui\Component;

class ExportButton extends \Magento\Ui\Component\ExportButton
{
    /**
     * @return void
     */
    public function prepare()
    {
        $config = $this->getConfig();
        $options = $config['options'];

        unset($options['xml']);
        unset($options['csv']);
        $config['options'] = $options;
        $this->setConfig($config);

        parent::prepare();
    }
}
