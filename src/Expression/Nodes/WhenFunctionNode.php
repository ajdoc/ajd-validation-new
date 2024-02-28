<?php

namespace AjdVal\Expression\Nodes;

use Ajd\Expression\Compiler\Compiler;
use Ajd\Expression\Nodes\FunctionNode;
use AjdVal\Expression\Traits;

class WhenFunctionNode extends FunctionNode
{
	use Traits\RuleNodeTrait;

	public function evaluate(array $functions, array $values): mixed
    {
        $compiler = new Compiler($functions);

        $arguments = [$values];
        foreach ($this->nodes['arguments']->nodes as $node) {
            $arguments[] = $compiler->subcompile($node);
        }
        
        $instance = $functions[$this->attributes['name']]['instance'] ?? null;
        
        if (! empty($instance)) {
            $this->setRuleInstance($instance);    
        }
        
        return $functions[$this->attributes['name']]['evaluator'](...$arguments);
    }
}