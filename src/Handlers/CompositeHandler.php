<?php 

namespace AjdVal\Handlers;

use AjdVal\Expression\ExpressionBehavior;
use AjdVal\Expression\ExpressionOperator;
use AjdVal\Expression\ExpressionStrategy;
use Stringable;

class CompositeHandler extends AbstractHandlers
{
	public function __construct(
		array $rules,
		ExpressionOperator|string $operator = ExpressionOperator::And,
		Stringable|string $message = '',
		ExpressionBehavior $behavior = ExpressionBehavior::Normal,
		ExpressionStrategy $strategy = ExpressionStrategy::FailLazy

	) {
		$this->rules = $rules;
		$this->operator = $operator;
		$this->message = $message;
		$this->behavior = $behavior;
		$this->strategy = $strategy;
	}
}