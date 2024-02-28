<?php

namespace AjdVal\Expression\Traits;

use AjdVal\Contracts;

trait RuleNodeTrait
{
	protected Contracts\RuleInterface|null $instance = null;

	protected bool $isReversed = false;

	public function setRuleInstance(Contracts\RuleInterface $instance): void 
    {
    	$this->instance = $instance;
    }

    public function getRuleInstance(): Contracts\RuleInterface|null
    {
    	return $this->instance;
    }

    public function setReversed(bool $reversed = false): void 
    {
    	$this->isReversed = $reversed;
    }

    public function isReversed(): bool 
    {
    	return $this->isReversed;
    }
}