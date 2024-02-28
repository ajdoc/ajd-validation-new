<?php 

namespace AjdVal\Expression;

use AjdVal\Traits;
use Ajd\Expression\Lexer\Lexer;
use AjdVal\Validators\ValidatorsInterface;
use AjdVal\Rules\Expr;
use AjdVal\Contracts\RuleInterface;
use AjdVal\Utils\Utils;
use ReflectionClass;

class ExpressionBuilderValidator extends ExpressionBuilder implements ValidatorsInterface
{
	use Traits\Sometimes;
	use Traits\ValidatorStateTrait;

	protected array $ruleInstances = [];

	protected int $ruleCount = 0;

	protected array $ruleArguments = [];

	public function __construct(protected ValidatorsInterface $validator)
	{
		
	}

	public function __call(string $rule, array $arguments): mixed
	{
		$isRule = false;

		if (! method_exists($this, $rule)) {

			$this->ruleArguments[] = $arguments;

			$this->ruleInstances[] = $this->validator->buildRule($rule, $arguments);
			$isRule = true;

			$rule = $rule.''.$this->ruleCount;

			$this->ruleCount++;
		}

		$arguments['isRule'] = $isRule;

		return parent::__call($rule, $arguments);
	}

	protected function processRuleDefinitions(): array 
	{
		$definitions = [];
		$arguments = [];

		foreach ($this->ruleInstances as $key => $rule) {

			$args = $this->ruleArguments[$key] ?? [];
			$scalarArgs = [];

			$definitions[][$rule->getName()] = $rule;
			$arguments[][$rule->getName()] = $args;
		}

		return [
			'definitions' => $definitions,
			'arguments' => $arguments,
			'sometimes' => $this->sometimes
		];
	}

	public function endexpr()
	{
		[
			'definitions' => $definitions, 
			'arguments' => $arguments,
			'sometimes' => $sometimes,
		] = $this->processRuleDefinitions();

		if (empty($definitions)) {
			return $this->validator;
		}

		$this->postProcessExpression($definitions, $arguments);
		
		return $this->validator->addRuleValidator(
			Expr::createRule(
				$this, 
				$this->validator, 
				$definitions, 
				$arguments, 
				$sometimes
			)
		);
	}


    public function postProcessExpression(array $definitions, array $arguments = []): void 
    {
     	foreach ($definitions as $ruleKey => $rules) {

            foreach ($rules as $name => $rule) {
            	$ruleName = $name.''.$ruleKey;
            	$passedArguments = $arguments[$ruleKey][$name] ?? [];

            	$ruleName = static::createRuleName($ruleName, $passedArguments, true);

            	$patternFollows = '/(' . preg_quote($ruleName). ')\s*(?:&&|\|\||xor)/';
            	$patternFollowsNot = '/(' . preg_quote('! '.$ruleName). ')\s*(?:&&|\|\||xor)/';
            	$patternPrecedes = '/(?:&&|\|\||xor)\s*(' . preg_quote($ruleName) . ')/';
            	$patternPrecedesNot = '/(?:&&|\|\||xor)\s*(' . preg_quote('! '.$ruleName) . ')/';

            	if (
            		(
            			preg_match_all($patternFollows, $this->buffer, $matchesFollows) ||
            			preg_match_all($patternFollowsNot, $this->buffer, $matchesFollowsNot)
            		) ||
            		(
            			preg_match_all($patternPrecedes, $this->buffer, $matchesPrecedes) ||
            			preg_match_all($patternPrecedesNot, $this->buffer, $matchesPrecedesNot)
            		)
            	) {

            	} else {

            		$this->buffer = str_replace($ruleName, $ruleName.' && true', $this->buffer);
            	}

            }
         }
    }

    public function build(): string
    {
    	[
			'definitions' => $definitions, 
			'arguments' => $arguments
		] = $this->processRuleDefinitions();

		$this->postProcessExpression($definitions, $arguments);

    	return $this->buffer;
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
            ->build();

        $this->open();
        $this->write($expression);
        $this->close();

        return $this;
    }
}