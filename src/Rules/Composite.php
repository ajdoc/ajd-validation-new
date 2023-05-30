<?php 

namespace AjdVal\Rules;

use AjdVal\Contracts\RuleInterface;
use AjdVal\Contracts\RuleIsExpressionInterface;
use AjdVal\Expression\ExpressionBehavior;
use AjdVal\Expression\ExpressionOperator;
use AjdVal\Expression\ExpressionStrategy;
use AjdVal\Expression\ExpressionBuilderValidator;
use AjdVal\Traits\ExpressionValidationTrait;
use AjdVal\Utils\Utils;
use AjdVal\Contracts\RuleCreatorInterface;
use AjdVal\Handlers\CompositeHandler;
use AjdVal\Handlers\HandlerDto;
use AjDic\AjDic;

#[Attribute(
    Attribute::TARGET_CLASS |
    Attribute::TARGET_CLASS_CONSTANT |
    Attribute::TARGET_PROPERTY |
    Attribute::TARGET_METHOD
)]
class Composite extends AbstractRule implements RuleIsExpressionInterface
{
	use ExpressionValidationTrait;

	public static RuleHandlerStrategy $ruleHandlerStrategy = RuleHandlerStrategy::NotAutoCreate;

	protected AjDic $container;

	protected static array $statDefinitions = [];
	protected array $definitions = [];

	protected HandlerDto $handler;

	public function __construct(HandlerDto|null $handler = null) 
	{
		$this->handler = $handler;	
		
		$customMessage = $this->handler?->message ?? '';

		if (is_string($this->handler?->operator) && empty($this->handler?->message)) {
			$customMessage = $this->handler?->operator;

			$this->handler->operator = ExpressionOperator::And;
		}
		
		$this->handler->message = $customMessage;

		$this->setErrorMessage($customMessage);
		$this->container = new AjDic;	
	}

	public static function setRuleHandlerStack(): void 
	{
		static::$handlerStack[self::class] = [
			CompositeHandler::class
		];
	}

	protected function combine(array $rules, ExpressionOperator $operator, ExpressionBehavior $behavior, ExpressionStrategy $strategy)
	{
		$validator = $this->getValidator();
		$expression = $validator->expr();
		$count = count($rules);
		
		$i = 0;
		foreach ($rules as $rule) {

			if (
				! $rule instanceof RuleInterface 
				&& ! $rule instanceof RuleIsExpressionInterface 
				&& ! $rule instanceof RuleCreatorInterface 
				&& ! is_string($rule)
			) {
				continue;
			}

			if ($operator === ExpressionOperator::Not) {
		        $expression->not();
		    }

		    if ($rule instanceof RuleCreatorInterface) {
		    	$rule = $rule->extractRule();
		    }

		    if ($rule instanceof RuleInterface) {
		    	$rule->setValidator($validator);
		    }

		    if ($rule instanceof RuleIsExpressionInterface) {

		    	$expression->add($rule->getExpression());

		    } else if (is_string($rule)) {
		    	$expression->add($rule);

		    } else if ($rule instanceof RuleInterface) {
		    	$rule->setValidatorDto($validator?->getValidatorDto());
		    	$name = Utils::getShortNameClass(get_class($rule));

		    	$arguments = $rule->getArguments();

		    	$expression->{$name}(...$arguments);

		    	$validator?->createNewExpressionRule(
	                $name,
	                $rule,
	                $this->container,
	                $arguments
	            );

		    	$definition = $rule->getExpressionDefinition();

		    	static::$statDefinitions[$definition->getName()] = $definition;
		    }

		    ($i + 1 < $count) && match ($operator) {
                ExpressionOperator::And => $expression->and(),
                ExpressionOperator::Or  => $expression->or(),
                ExpressionOperator::Xor => $expression->xor(),
                default       => $expression->and(),
            };

		    $i++;

		}

		match ($behavior) {
            ExpressionBehavior::Normal      => $expression->normal(),
            ExpressionBehavior::Optimistic  => $expression->optimistic(),
            ExpressionBehavior::Pessimistic => $expression->pessimistic(),
        };

        return $expression;
	}

	public function getExpression(): ExpressionBuilderValidator|string
	{
		return $this->combine($this->handler?->rules, $this->handler?->operator, $this->handler?->behavior, $this->handler?->strategy);

		return '';
	}

	public function validate(mixed $value, string $path = ''): bool
	{
		$expression = $this->getExpression();
		
		$this->definitions = static::$statDefinitions;
		
		return $this->validateOne($value, $expression);
	}
}