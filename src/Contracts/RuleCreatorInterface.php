<?php 

namespace AjdVal\Contracts;


interface RuleCreatorInterface
{
	/**
     * Validates the value against set of rules.
     *
     * @return \AjdVal\Contracts\RuleInterface
     */
	public function extractRule(): RuleInterface;
}