<?php 

namespace AjdVal\Traits;

use AjdVal\Expression\ExpressionEngine;
use AjdVal\Expression\ExpressionBehavior;
use AjdVal\Expression\ExpressionBuilderValidator;
use AjdVal\Utils\Utils;
use AjdVal\Exceptions\ValidationExceptions;
use AjdVal\Expression\ExpressionValidationErrorMessages;
use AjdVal\Expression\ExpressionValidation as AjdExpression;
use AjdVal\Contracts\RuleInterface;
use InvalidArgumentException;
use Throwable;

trait ExpressionValidationTrait
{
    protected function replaceExpression(string $expression, string $type): string
    {
        $match = preg_match('/&&|\|\||xor/', $expression, $matches);
        $operator = $matches[0] ?? '&&';


        return match($type) {
            'follows' => 'true '.$operator,
            'precedes' => $operator.' true'
        };
        
    }

    protected function processSometimes(
        mixed $value,
        string|bool|callable|null $sometime = null,
        RuleInterface|null $rule = null
    ): bool
    {
        if (is_string($sometime)) {
            return (new AjdExpression)->evaluate($sometime);
        }

        if(is_bool($sometime)) {
            return $sometime;
        }

        if (is_callable($sometime)) {
            return (bool) $sometime($value, $this, $rule);
        }

        if (is_null($sometime)) {
            return ! empty($value);
        }
    }

    protected function removeRuleInExpression(string $ruleName, string $expression): string
    {
        $patternFollows = '/(' . preg_quote($ruleName). ')\s*(?:&&|\|\||xor)/';
        $patternFollowsNot = '/(' . preg_quote('! '.$ruleName). ')\s*(?:&&|\|\||xor)/';
        $patternPrecedes = '/(?:&&|\|\||xor)\s*(' . preg_quote($ruleName) . ')/';
        $patternPrecedesNot = '/(?:&&|\|\||xor)\s*(' . preg_quote('! '.$ruleName) . ')/';

        if (
            (
                preg_match_all($patternFollows, $expression, $matchesFollows) ||
                preg_match_all($patternFollowsNot, $expression, $matchesFollowsNot)
            ) ||
            (
                preg_match_all($patternPrecedes, $expression, $matchesPrecedes) ||
                preg_match_all($patternPrecedesNot, $expression, $matchesPrecedesNot)
            )
        ) {

            if(! empty($matchesFollows[0])) {
                
                return str_replace($matchesFollows[0][0], $this->replaceExpression($matchesFollows[0][0], 'follows'), $expression);
            }

            if(! empty($matchesFollowsNot[0])) {
                return str_replace($matchesFollowsNot[0][0], $this->replaceExpression($matchesFollowsNot[0][0], 'follows'), $expression);
            }

            if(! empty($matchesPrecedes[0])) {
                return str_replace($matchesPrecedes[0][0], $this->replaceExpression($matchesPrecedesNot[0][0], 'precedes'), $expression);
            }

            if(! empty($matchesPrecedesNot[0])) {
                
                return str_replace($matchesPrecedesNot[0][0], $this->replaceExpression($matchesPrecedesNot[0][0], 'precedes'), $expression);
            }
        }

        return $expression;
    }

    public function validateOne(mixed $value, ExpressionBuilderValidator|string $validation): bool
    {
        $expression = new AjdExpression;

        $validation = trim($validation);

        foreach ($this->definitions as $ruleKey => $rules) {

            $sometimes = $this->sometimes[$ruleKey] ?? [];
            $ruleArguments = $this->ruleArguments[$ruleKey] ?? [];

            foreach ($rules as $name => $rule) {
                $ruleName = $name.''.$ruleKey;
                $arguments = $ruleArguments[$name] ?? [];
                $compiledRule = ExpressionBuilderValidator::createRuleName($ruleName, $arguments, true);
                $runSometimes = false;
                $runRule = true;

                if (array_key_exists($ruleName, $sometimes)) {
                    $runSometimes = true;
                }

                if ($runSometimes) {
                    $sometime = $sometimes[$ruleName];
                    $runRule = $this->processSometimes($value, $sometime, $rule);
                }

                if (! $runRule) {
                    $validation = $this->removeRuleInExpression($compiledRule, $validation);
                }
                
                if ($runRule) {
                    $expression->registerRule(
                        $ruleName,
                        $rule,
                        function() use($compiledRule, $rule, $ruleKey): string {
                            return $compiledRule;
                        },
                        function() use($ruleName, $rule, $value, $sometimes): mixed {
                            return $rule->validate($value);
                        }
                    );   
                }
            }

        }

        $result = $expression->evaluate($validation);
        
        $errors = $result ? [] : ExpressionValidationErrorMessages::getErrorMessages();

        $adhocErrors = $this->getAdhocErrors();
        
        if (! empty($errors)) {
            
            if (empty($adhocErrors)) {
                $this->setAdhocErrors($errors);
            }

            if (! empty($adhocErrors)) {
                $this->setAdhocErrors(array_merge($adhocErrors, $errors));   
            }
        }

        ExpressionValidationErrorMessages::reset(); 

        return $result;
    }
}