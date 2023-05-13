<?php 

namespace AjdVal\Rules;

use AjdVal\Contracts;

abstract class AbstractAll extends AbstractRule
{
	protected $rules = [];

	public function removeRules(): void
    {
        $this->rules = [];
    }

	public function addRules(array $rules): static
    {
    	foreach ($rules as $key => $rule) {
    		if ($rule instanceof Contracts\RuleInterface) {
    			$this->appendRule($rule);
    		} else if (is_numeric($key) && is_array($rule)) {
    			$this->addRules($rule);
    		} else if (is_array($rule)) {
    			$this->addRuleValidator($key, $rule);
    		} else {
    			$this->addRuleValidator($rule);
    		}
    	}

    	return $this;
    }

    public function addRuleValidator($rule, array $arguments = []): static
    {
    	if (! $rule instanceof Contracts\RuleInterface ) {
    		return $this;
    	} 
    	
    	$this->appendRule($rule);

    	return $this;
    }
	
	protected function appendRule(Contracts\RuleInterface $rule)
    {
        $this->rules[spl_object_hash($rule)] = $rule;
    }

    public function removeRule(Contracts\RuleInterface $rule)
    {
        unset($this->rules[spl_object_hash($rule)]);
    }

    public function getRules()
    {
        return $this->rules;
    }
}