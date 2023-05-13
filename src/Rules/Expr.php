<?php 

namespace AjdVal\Rules;

use AjdVal\Expression\ExpressionBuilderValidator;
use AjdVal\Traits\ExpressionValidationTrait;
use AjdVal\Validators\ValidatorsInterface;

class Expr extends AbstractRule
{
	use ExpressionValidationTrait;
	
	public function __construct(
		protected ExpressionBuilderValidator $expressionBuilder, 
		protected ValidatorsInterface $validator,
		protected array $definitions = []
	) {
		
	}

	public function validate(mixed $value, string $path = ''): bool
	{
		return $this->validateOne($value, $this->expressionBuilder);
	}
}