<?php 

namespace AjdVal\Rules;

use AjdVal\Expression\ExpressionBuilderValidator;
use AjdVal\Traits\ExpressionValidationTrait;
use AjdVal\Contracts\RuleIsExpressionInterface;
use AjdVal\Handlers\HandlerDto;
use AjdVal\Handlers\ExprHandler;

class Expr extends AbstractRule implements RuleIsExpressionInterface
{
	use ExpressionValidationTrait;

	public static RuleHandlerStrategy $ruleHandlerStrategy = RuleHandlerStrategy::NotAutoCreate;
	protected array $definitions = [];
	
	public function __construct(
		protected HandlerDto|null $handler = null
	) {
		$this->definitions = $this->handler?->definitions ?? [];
	}

	public static function setRuleHandlerStack(): void 
	{
		static::$handlerStack[self::class] = [
			ExprHandler::class
		];
	}

	public function getExpression(): ExpressionBuilderValidator|string
	{
		return $this->handler?->expressionBuilder;
	}

	public function validate(mixed $value, string $path = ''): bool
	{
		if (empty($this->handler?->definitions)) {
			return false;
		}

		return $this->validateOne($value, $this->handler?->expressionBuilder);
	}
}