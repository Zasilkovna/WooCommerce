<?php

declare(strict_types=1);

namespace Packetery;

class LatteProcessor
{
    /** @var \Latte\Engine */
    private $latteEngine;

    /**
     * LatteProcessor constructor.
     *
     * @param \Latte\Engine $latteEngine
     */
    public function __construct(\Latte\Engine $latteEngine) {
        $latteEngine->onCompile[] = function ($latte) {
            \Nette\Bridges\FormsLatte\FormMacros::install($latte->getCompiler());
        };
        $this->latteEngine = $latteEngine;
    }

    /**
     * @param string $template filepath
     * @param array $params
     */
    public function render(string $template, array $params): void {
        $this->latteEngine->render($template, $params);
    }

    /**
     * @param string $template
     * @param array $params
     * @return string
     */
    public function renderToString(string $template, array $params): string {
        return $this->latteEngine->renderToString($template, $params);
    }
}
