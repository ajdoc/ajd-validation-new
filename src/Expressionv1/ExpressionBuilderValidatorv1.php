<?php 

namespace AjdVal\Expression;

use AjdVal\Validators\ValidatorsInterface;
use AjdVal\Rules\Expr;
use AjdVal\Contracts\RuleInterface;
use AjdVal\Utils\Utils;
use ReflectionClass;

class ExpressionBuilderValidator extends ExpressionBuilder implements ValidatorsInterface
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

		if (empty($definitions)) {
			return $this->validator;
		}
		
		return $this->validator->addRuleValidator(Expr::createRule($this, $this->validator, $definitions));
	}

	public function validate(mixed $value, string $path = '')
	{
		$validator = $this->endexpr()->validate($value, $path);

		$this->reset();

		return $validator;
	}

	public function getValidator(): ValidatorsInterface
	{
		return $this->validator;
	}

	 /**
     * Adds a validation string or object as a group to the current validation expression.
     *
     * @param string|Expression $validation Validation expression string or object.
     *
     * @return static
     */
    public function add(string|ExpressionBuilder $expr): static
    {
        // make sure the added expression has no behavior
        $expression = (new parent())
            ->write((string)$expr)
            ->normal()
            ->build();

        $this->open();
        $this->write($expression);
        $this->close();

        return $this;
    }
}