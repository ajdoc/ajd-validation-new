<?php 

namespace AjdVal\Traits;

use AjdVal\Expression;

trait ValidatorsTrait
{
	public function expr(): Expression\ExpressionBuilderValidator
	{
		return new Expression\ExpressionBuilderValidator($this);
	}
}