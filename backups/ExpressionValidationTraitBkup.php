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
        $operatpr = $matches[0] ?? '&&';

        return $operatpr.' true';
    }

    protected function processSometimes(
        mixed $value,
        string|bool|callable|null $sometime = null
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
                    $runRule = $this->processSometimes($value, $sometime);
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

        // var_dump($result);die;
        try {
            $basis = strval($validation);

            $validation = trim($validation);
            $behavior   = substr($validation, 0, 1);
            
            $behaviors  = [
                ExpressionBehavior::Optimistic->value  => true,
                ExpressionBehavior::Pessimistic->value => false,
            ];
            $behavior   = $behaviors[$behavior] ?? null;
            $validation = $behavior === null ? $validation : substr($validation, 1);

            $expression  = $rules = ExpressionEngine::cleanExpression($validation);
            $results     = [];
            $validations = [];
            $metadata = [];

            $checks = ExpressionEngine::parseExpression($expression);
            
            foreach ($checks as $key => ['name' => $name, 'statement' => $statement]) {
                $isReversed = false;

                if ((bool) preg_match('/[\~]/', $name) !== false) {
                    $isReversed = true;
                }

                $name = str_replace('~', '', $name);
                
                $resultDetails = $this->executeRule($name, $statement, $value);
                $result = $resultDetails['result'];

                $validations[$key][$name] = $results[$key][$statement] = $result;
                $metadata[$key][$name] = $resultDetails;
                $metadata[$key][$name]['isReversed'] = $isReversed;

                if ($result === $behavior) {  

                    $names       = $this->cleanNameToArray(array_column($checks, 'name'));
                    $statements  = array_column($checks, 'statement');
                    $nameArr  = [];

                    foreach ($names as $name) {
                        $nameArr[][$name] = null;
                    }

                    foreach ($validations as $keyR => $validation) {
                        foreach ($validation as $name => $result) {
                            $validations[$keyR][$name] = $nameArr[$keyR][$name] ?? $result;
                        }
                    }

                    $filledChecks = array_fill(0, count($checks), $behavior);
                    $realStatement = [];
                    foreach ($statements as $keyS => $ruleName) {
                        $realStatement[$keyS][$ruleName] = $filledChecks[$keyS] ?? false;
                    }

                    $realResults = [];
                    foreach ($results as $keyB => $result) {
                        foreach ($result as $name => $r) {
                            $realResults[$keyB][$name] = $realStatement[$keyB][$name] ?? $r;
                        }
                    }
                    
                    $results = $realResults;

                    $realMetadata = [];

                    foreach ($metadata as $keyC => $data) {
                        foreach ($data as $name => $r) {
                            $realMetadata[$keyC][$name] = $r;
                            $realMetadata[$keyC][$name]['result'] = $realStatement[$keyC][$name] ?? $r;
                        }
                    }

                    $metadata = $realMetadata;
                }
            }
            
            ['result' => $result, 'expression' => $expression] = ExpressionEngine::evaluateExpression($expression, $results, true);
            
            $errors = $result ? [] : $this->createErrorMessages($metadata);

            $adhocErrors = $this->getAdhocErrors();
            
            if (! empty($errors)) {
                
                if (empty($adhocErrors)) {
                    $this->setAdhocErrors($errors);
                }

                if (! empty($adhocErrors)) {
                    $this->setAdhocErrors(array_merge($adhocErrors, $errors));   
                }
            }

            return $result;
        } catch (Throwable $e) {
            return false;
        }
    }

    protected function cleanNameToArray(array $names): array 
    {
        foreach($names as $key => $name) {
            $name = str_replace('~', '', $name);

            $names[$key] = $name;
        }

        return $names;
    }

    protected function executeRule(string $name, string $statement, $input): array
    {
        $rule = $this->definitions[$name] ?? null;
        /** @var Rule|null $rule */
        if ($rule === null) {

            $names = array_merge(
                array_keys($this->definitions),
            );
            $keywords = Utils::transform($name, 'clean', 'lower');
            $keywords = explode(' ', $keywords);
            $keywords = array_filter($keywords, fn ($keyword) => strlen($keyword) > 1);
            $keywords = array_map(fn ($keyword) => preg_quote($keyword, '/'), $keywords);
            $keywords = array_reduce($keywords, fn ($carry, $item) => trim($carry . '|' . $item, '|'), '');
            $matches  = preg_grep("/({$keywords})/i", $names) ?: ['(no matches found)'];
            $matches  = implode('", "', $matches);

            throw new InvalidArgumentException(
                Utils::interpolate(
                    'Unknown rule: "{name}" is unknown. Did you mean: "{matches}"? If not, ' .
                    'check if the rule with given name was added or the default rules were loaded successfully',
                    compact('name', 'matches')
                )
            );
        }

        $result = $rule
            ->setStatement($statement)
            ->setInput($input)
            ->execute();

        return ['result' => (bool)($result), 'rule' => $rule];
    }

    protected function createErrorMessages(array $metadata): array
    {
        $errors = [
            'messages' => [],
            'rules' => [],
            'exceptions' => []
        ];

        foreach ($metadata as $key => $metadata) {

            foreach ($metadata as $data) {
                if ($data['isReversed']) {
                    $check = $data['result'] !== false;
                } else {
                    $check = $data['result'] === false;
                }

                if ($check) {
                    $rule = $data['rule'];

                    $instance = $rule->getRuleInstance();

                    $exception = $instance->reportError(null);
                    
                    if ($data['isReversed']) {
                        $exception->setMode(ValidationExceptions::ERR_NEGATIVE);
                    }

                    $adhocErr = $instance->getAdhocErrors();
                    
                    if (isset($adhocErr['messages']) && ! empty($adhocErr['messages'])) {
                        $errors['messages'] = array_merge($errors['messages'], $adhocErr['messages']);
                    } else {
                        $errors['messages'][] = $exception->getExceptionMessage();       
                    }

                    if (isset($adhocErr['rules']) && ! empty($adhocErr['rules'])) {
                        $errors['rules'] = array_merge($errors['rules'], $adhocErr['rules']);
                    } else {
                        $errors['rules'][] = $instance;
                    }

                    if (isset($adhocErr['exceptions']) && ! empty($adhocErr['exceptions'])) {
                        $errors['exceptions'] = array_merge($errors['exceptions'], $adhocErr['exceptions']);                                
                    } else {
                        $errors['exceptions'][] = $exception;
                    }
                }
            }
        }
        
        return $errors;
    }
}