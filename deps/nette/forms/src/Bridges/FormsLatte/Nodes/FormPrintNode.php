<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Bridges\FormsLatte\Nodes;

use Packetery\Latte\Compiler\Nodes\Php\ExpressionNode;
use Packetery\Latte\Compiler\Nodes\StatementNode;
use Packetery\Latte\Compiler\PrintContext;
use Packetery\Latte\Compiler\Tag;
/**
 * {formPrint [ClassName]}
 * {formClassPrint [ClassName]}
 */
class FormPrintNode extends StatementNode
{
    public ?ExpressionNode $name;
    public string $mode;
    public static function create(Tag $tag) : static
    {
        $node = new static();
        $node->name = $tag->parser->isEnd() ? null : $tag->parser->parseUnquotedStringOrExpression();
        $node->mode = $tag->name;
        return $node;
    }
    public function print(PrintContext $context) : string
    {
        return $context->format('\\Packetery\\Nette\\Bridges\\FormsLatte\\Runtime::render%raw(' . ($this->name ? 'is_object($ʟ_tmp = %node) ? $ʟ_tmp : $this->global->uiControl[$ʟ_tmp]' : 'end($this->global->formsStack)') . ') %2.line; exit;', $this->mode, $this->name, $this->position);
    }
    public function &getIterator() : \Generator
    {
        if ($this->name) {
            (yield $this->name);
        }
    }
}
