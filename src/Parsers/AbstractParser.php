<?php 

namespace AjdVal\Parsers;

use AjDic\AjDic;
use InvalidArgumentException;
use AjdVal\Contracts\RuleInterface;
use AjdVal\Contracts\RuleIsExpressionInterface;
use AjdVal\Rules;
use AjdVal\Utils\Utils;
use AjdVal\Validators\ValidatorsInterface;

abstract class AbstractParser implements ParserInterface
{
	protected AjDic|null $container;
    protected object|null $validator = null;

    public function setContainer(AjDic $container): void
    {
        $this->container = $container;
    }

    public function getContainer(): AjDic|null
    {
        return $this->container;
    }

    public function setValidator(ValidatorsInterface $validator): void
    {
        $this->validator = $validator;
    }

    public function getValidator(): ValidatorsInterface|null
    {
        return $this->validator;   
    }

    public function resolveHandler(string $qualifiedClass, AjDic|null $container = null, ValidatorsInterface|null $validator = null, array $arguments = []): array
    {
        if (! empty($validator) && ! empty($container)) {
            $builder = $validator?->getValidatorDto()->getValidatorBuilder();
            $handlers = $validator?->resolveHandler($builder, Utils::getShortNameClass($qualifiedClass), $qualifiedClass, $container, $arguments);
            
            return $handlers;
        }

        return [];
    }

    public function resolveToExpr(
        RuleInterface $instance, 
        string $name, 
        array $arguments = [], 
        AjDic|null $container = null, 
        ValidatorsInterface|null $validator = null,
        array $handlers = []

    ): RuleIsExpressionInterface {

        if (! empty($validator)) {
            $validator?->initRuleHandlers($handlers, $instance, $arguments);
            $instance->setValidator($validator);    
        }

        $instance->setValidatorDto($validator?->getValidatorDto());

        if ($instance instanceof Rules\Composite) {
            return $instance;
        } else if ($instance instanceof RuleInterface) {

            $validator?->createNewExpressionRule(
                Utils::getShortNameClass($name),
                $instance,
                $container,
                $arguments
            );

            $definition = $instance->getExpressionDefinition();

            $definitions = [
                $definition->getName() => $definition
            ];

            $expression = $validator?->expr();

            $statement = $expression->createRuleStatement($definition->getName(), $arguments);
            $expression->write($statement);
            
            $expr = Rules\Expr::createRule($expression, $validator, $definitions);

            return $expr;
        }
    }

}