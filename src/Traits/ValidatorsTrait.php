<?php 

namespace AjdVal\Traits;

use AjdVal\Expression;
use AjDic\AjDic;
use AjdVal\Contracts\RuleInterface;
use AjdVal\Contracts\HandlerInterface;
use AjdVal\Builder\ValidatorBuilderInterface;
use AjdVal\Handlers\HandlerDto;
use Closure;
use ReflectionClass;

trait ValidatorsTrait
{
	public function expr(): Expression\ExpressionBuilderValidator
	{
		return new Expression\ExpressionBuilderValidator($this);
	}

	public static function resolveRule(ValidatorBuilderInterface $validatorBuilder, string $rule, array $arguments = []): Closure
	{
		$self = static::class;

		return function(AjDic $container, string $qualifiedClass, array $paramaters) use ($validatorBuilder, $rule, $self) {

			$handlers = ValidatorsTrait::resolveHandler($validatorBuilder, $rule, $qualifiedClass, $container, $paramaters);

			$paramaters = $self::processHandlerPreInit($paramaters, $qualifiedClass, $handlers);
			$instance = $container->makeWith($qualifiedClass, $paramaters);

			ValidatorsTrait::initRuleHandlers($handlers, $instance, $paramaters);
   			
   			ValidatorsTrait::createNewExpressionRule($rule, $instance, $container, $paramaters);

   			return $instance;
		};
	}

	public static function initRuleHandlers(array $handlers, RuleInterface $rule, array $arguments = []): void
	{
		if (empty($handlers)) {
			return;
		}

		$rule->setRuleHandlers($handlers);

		if (isset($arguments[0]) && $arguments[0] instanceof HandlerDto) {
			$rule->setHandlerDto([], $arguments[0]);
		} else {
			$rule->setHandlerDto();
		}

		ValidatorsTrait::setHandlerRuleObj($handlers, $rule);
	}

	public static function resolveHandler(ValidatorBuilderInterface $validatorBuilder, string $rule, string $qualifiedClass, AjDic $container, array $arguments = []): array
	{
		$handlerArr = [];

		$handler = $validatorBuilder->getRulesHandlerFactory()->generate($rule, null, ...$arguments);

		if (! empty($handler)) {
			$handlerArr[] = $handler;
		}

		$ruleHandlerStack = $qualifiedClass::getHandlerStack()[$qualifiedClass] ?? [];

		if (empty($ruleHandlerStack)) {
			$qualifiedClass::setRuleHandlerStack();

			$ruleHandlerStack = $qualifiedClass::getHandlerStack()[$qualifiedClass] ?? [];
		}
		
		if (! empty($ruleHandlerStack)) {
			foreach ($ruleHandlerStack as $handlerKey => $handlerStack) {
				if (!class_exists($handlerStack)) {
					continue;
				}

				$reflectionHandlerStack = new ReflectionClass($handlerStack);

				$interfaces  = array_keys($reflectionHandlerStack->getInterfaces());

				if (!in_array(HandlerInterface::class, $interfaces, true)) {
					continue;
				}

				$checkHandlerKey = isset($handlerArr[$handlerKey]) ? $handlerKey : $handlerKey - 1;

				$classArgs = $arguments;

				/*if (isset($handlerArr[$checkHandlerKey])) {
					$classArgs[] = $handlerArr[$checkHandlerKey];
				}*/

				$handlerStackObj = $container->makeWith($handlerStack, $classArgs);

				if (isset($handlerArr[$checkHandlerKey])) {
					$handlerStackObj->setPreviousHandler($handlerArr[$checkHandlerKey]);
				}

				$handlerArr[] = $handlerStackObj;
			}
		}
		
		return $handlerArr;
	}

	public static function setHandlerRuleObj(array $handlers, RuleInterface $rule): void
	{
		if (empty($handlers)) {
			return;
		}

		array_walk($handlers, function($handler) use ($rule) {
			$handler->setRuleObject($rule);
		});
	}

	public static function createNewExpressionRule(string $rule, RuleInterface $instance, AjDic $container, array $paramaters = []): void
	{
		$expressionRule = new Expression\ExpressionRuleCreator;

		$newRule = $expressionRule->name($rule)
   			->callback(function(mixed $input, array $argumentExpr = []) use($paramaters, $instance, $container) {
   				return $container->call([$instance, 'validate'], [$input]);
   			})
   			->parameters(['@input', '@arguments'])
   			->setRuleInstance($instance);

   		$instance->setExpressionDefinition($newRule);
	}
}