<?php

declare(strict_types=1);

namespace Packetery\Checkout\Model\Misc;

use Magento\Framework\Phrase;

class ComboPhrase extends Phrase
{
    /** @var array */
    private $phrases;

    /** @var Phrase|string */
    private $separator;

    /**
     * ComboPhrase constructor.
     *
     * @param array $phrases
     * @param \Magento\Framework\Phrase|string $separator
     */
    public function __construct(array $phrases, $separator = '') {
        $this->phrases = $phrases;
        $this->separator = $separator;
    }

    /**
     * @return string
     */
    public function render(): string {
        return implode(
            ($this->separator instanceof Phrase ? $this->separator->render() : (string)$this->separator),
            array_map(
                function ($phrase) {
                    return ($phrase instanceof Phrase ? $phrase->render() : (string)$phrase);
                },
                $this->phrases
            )
        );
    }

    /**
     * @return string
     */
    public function getText(): string {
        return implode(
            ($this->separator instanceof Phrase ? $this->separator->render() : (string)$this->separator),
            array_map(
                function ($phrase) {
                    return ($phrase instanceof Phrase ? $phrase->getText() : (string)$phrase);
                },
                $this->phrases
            )
        );
    }

    /**
     * @return array
     */
    public function getArguments(): array {
        return [];
    }
}
