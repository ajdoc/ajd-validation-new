<?php 

namespace AjdVal\Traits;

use AjdVal\Expression\ExpressionEngine;
use AjdVal\Expression\ExpressionBehavior;
use AjdVal\Expression\ExpressionBuilderValidator;
use AjdVal\Utils\Utils;
use AjdVal\Exceptions\ValidationExceptions;
use InvalidArgumentException;

trait ExpressionValidationTrait
{
	public function validateOne(mixed $value, ExpressionBuilderValidator|string $validation): bool
    {
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
        
        foreach ($checks as ['name' => $name, 'statement' => $statement]) {
            $isReversed = false;

            if ((bool) preg_match('/[\~]/', $name) !== false) {
                $isReversed = true;
            }

            $name = str_replace('~', '', $name);
            $statement = str_replace('~', '', $statement);
            
            $resultDetails = $this->executeRule($name, $statement, $value);
            $result = $resultDetails['result'];

            $validations[$name] = $results[$statement] = $result;

            $metadata[$name] = $resultDetails;
            $metadata[$name]['isReversed'] = $isReversed;

            if ($result === $behavior) {                
                $names       = array_column($checks, 'name');
                $statements  = array_column($checks, 'statement');
                $validations = array_merge(array_fill_keys($names, null), $validations);
                $metadata    = array_merge(array_fill_keys($names, null), $metadata);
                $results     = array_merge(array_combine($statements, array_fill(0, count($checks), $behavior)), $results);

                break;
            }
        }
        
        ['result' => $result, 'expression' => $expression] = ExpressionEngine::evaluateExpression($expression, $results);

        $errors = $result ? [] : $this->createErrorMessages($metadata);
        
        if (!empty($errors)) {
            $this->setAdhocErrors($errors);
        }

        return $result;
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
        
        $errors = array_filter($metadata, function($metadata) {
            if ($metadata['isReversed']) {
                return $metadata['result'] !== false;   
            } else {
                return $metadata['result'] === false;    
            }
        });

        /** @var array<string,string> $errors */
        // make a message for each validation and inject the necessary variables
        array_walk($errors, function (&$value, $name) {
            $rule = $value['rule'];
            $instance = $rule->getRuleInstance();

            $exception = $instance->reportError(null);
            
            if ($value['isReversed']) {
                $exception->setMode(ValidationExceptions::ERR_NEGATIVE);
            }

            $value = $exception->getExceptionMessage();
        });

        return $errors;
    }
}