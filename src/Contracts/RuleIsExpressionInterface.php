<?php 

namespace AjdVal\Contracts;

use AjdVal\Expression\ExpressionBuilderValidator;

interface RuleIsExpressionInterface
{
	/**
     * Get the Expresion
     *
     * @return mixed
     */
    public function getExpression(): ExpressionBuilderValidator|string;
}