<?php

namespace AjdVal\Expression\Nodes;

use Ajd\Expression\Compiler\Compiler;
use Ajd\Expression\Nodes\FunctionNode;
use AjdVal\Expression\Traits;

class ValidationNode extends FunctionNode
{
	use Traits\RuleNodeTrait;

	public function evaluate(array $functions, array $values): mixed
    {
        $arguments = [$values];
        foreach ($this->nodes['arguments']->nodes as $node) {
            $arguments[] = $node->evaluate($functions, $values);
        }

        $instance = $functions[$this->attributes['name']]['instance'];
        
        $this->setRuleInstance($instance);

        $result = $functions[$this->attributes['name']]['evaluator'](...$arguments);

        return $result;
    }
}