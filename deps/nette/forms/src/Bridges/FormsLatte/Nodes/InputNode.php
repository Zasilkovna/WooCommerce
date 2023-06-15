<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Packetery\Nette\Bridges\FormsLatte\Nodes;

use Packetery\Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Packetery\Latte\Compiler\Nodes\Php\ExpressionNode;
use Packetery\Latte\Compiler\Nodes\Php\Scalar\StringNode;
use Packetery\Latte\Compiler\Nodes\StatementNode;
use Packetery\Latte\Compiler\PrintContext;
use Packetery\Latte\Compiler\Tag;
/**
 * {input ...}
 */
class InputNode extends StatementNode
{
    public ExpressionNode $name;
    public ?ExpressionNode $part = null;
    public ArrayNode $attributes;
    public static function create(Tag $tag) : static
    {
        $tag->outputMode = $tag::OutputKeepIndentation;
        $tag->expectArguments();
        $node = new static();
        $node->name = $tag->parser->parseUnquotedStringOrExpression(colon: \false);
        if ($tag->parser->stream->tryConsume(':')) {
            $node->part = $tag->parser->isEnd() || $tag->parser->stream->is(',') ? new StringNode('') : $tag->parser->parseUnquotedStringOrExpression();
        }
        $tag->parser->stream->tryConsume(',');
        $node->attributes = $tag->parser->parseArguments();
        return $node;
    }
    public function print(PrintContext $context) : string
    {
        return $context->format('echo \\Packetery\\Nette\\Bridges\\FormsLatte\\Runtime::item(%node, $this->global)->' . ($this->part ? 'getControlPart(%node)' : 'getControl()') . ($this->attributes->items ? '->addAttributes(%2.node)' : '') . ' %3.line;', $this->name, $this->part, $this->attributes, $this->position);
    }
    public function &getIterator() : \Generator
    {
        (yield $this->name);
        if ($this->part) {
            (yield $this->part);
        }
        (yield $this->attributes);
    }
}
