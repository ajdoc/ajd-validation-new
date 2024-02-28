<?php

namespace AjdVal\Expression\Nodes;

use Ajd\Expression\Compiler\Compiler;
use Ajd\Expression\Lexer\SyntaxError;
use Ajd\Expression\Nodes\UnaryNode;
use AjdVal\Expression\Traits;

class ValidationUnaryNode extends UnaryNode
{
    use Traits\RuleNodeTrait;

    public function evaluate(array $functions, array $values): mixed
    {
        $value = $this->nodes['node']->evaluate($functions, $values);

        $this->setRuleInstance($this->nodes['node']->getRuleInstance());

        $this->setReversed(false);
        if ($this->attributes['operator'] === '!') {
            $this->setReversed(true);
        }
        
        return match ($this->attributes['operator']) {
            'not',
            '!' => !$value,
            '-' => -$value,
            default => $value,
        };
    }
}
