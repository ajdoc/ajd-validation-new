<?php 

namespace AjdVal\Expression;

use AjdVal\Validators\ValidatorsInterface;
use AjdVal\Rules\Expr;

class ExpressionBuilderValidator extends ExpressionBuilder
{
	protected array $ruleInstances = [];

	public function __construct(protected ValidatorsInterface $validator)
	{
		
	}

	public function __call(string $rule, array $arguments): mixed
	{
		if (! method_exists($this, $rule)) {
			$this->ruleInstances[] = $this->validator->buildRule($rule, $arguments);
		}

		return parent::__call($rule, $arguments);
	}

	public function endexpr()
	{
		$definitions = [];
		foreach ($this->ruleInstances as $rule) {
			if($definition = $rule->getExpressionDefinition()) {
				$definitions[$definition->getName()] = $rule->getExpressionDefinition();
			}
		}
		
		return $this->validator->addRuleValidator(new Expr($this, $this->validator, $definitions));
	}

	public function validate(mixed $value, string $path = '')
	{
		return $this->endexpr()->validate($value, $path);
	}

	public function getValidator(): ValidatorsInterface
	{
		return $this->validator;
	}
}