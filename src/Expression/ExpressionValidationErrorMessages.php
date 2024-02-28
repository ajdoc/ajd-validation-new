<?php

namespace AjdVal\Expression;

use AjdVal\Exceptions\ValidationExceptions;

final class ExpressionValidationErrorMessages
{
	protected static $errors = [
		'messages' => [],
        'rules' => [],
        'exceptions' => []
	];

	public static function createErrorMessages(array $leftDetails, array $rightDetails, array $details): array 
	{
		$errorsOperator = static::$errors['messages'][$details['operator']] ?? [];

		switch ($details['operator']) {
            case 'or':
            case '||':
                if (!$details['result']) {
                	
                	if (count($errorsOperator) <= 0) {
						static::addError($leftDetails, $details['operator']);	
                	} else {
                		if (empty($rightDetails['instance'])) {
	                		static::addError($leftDetails, $details['operator']);	
	                	}
                	}
                	
                	static::addError($rightDetails, $details['operator']);
                }
            break;
            case 'and':
            case '&&':
            	if (count($errorsOperator) <= 0) {
                	static::addError($leftDetails, $details['operator']);
                } else {
                	if (empty($rightDetails['instance'])) {
                		static::addError($leftDetails, $details['operator']);	
                	}
                }

                static::addError($rightDetails, $details['operator']);

            break;
            case 'xor':
                if (!$details['result']) {
                	if (count($errorsOperator) <= 0) {
                		static::addError($leftDetails, $details['operator'], $details['result']);
                	} else {
                		if (empty($rightDetails['instance'])) {
	                		static::addError($leftDetails, $details['operator']);	
	                	}
                	}
                	
                	static::addError($rightDetails, $details['operator'], $details['result']);
                }
            break;
        }

		return static::$errors;
	}

	public static function addError(array $details, string $operator, bool|null $result = null): void
	{	
		if (empty($details['instance'])) {	
			return;
		}
		
		$check = $details['result'] === false;

		if (
			!$check
			&& (
				is_null($result)
				|| $result === true
			)
		) {
			return;
		}

		$instance = $details['instance'];

		$exception = $instance->reportError(null);
                    
        if ($details['reversed']) {
            $exception->setMode(ValidationExceptions::ERR_NEGATIVE);
        }

        $adhocErr = $instance->getAdhocErrors();

        if (isset($adhocErr['messages']) && ! empty($adhocErr['messages'])) {
            static::$errors['messages'][$operator] = array_merge(static::$errors['messages'][$operator] ?? [], $adhocErr['messages']);
        } else {
            static::$errors['messages'][$operator][] = $exception->getExceptionMessage(); 
        }

        if (isset($adhocErr['rules']) && ! empty($adhocErr['rules'])) {
            static::$errors['rules'][$operator] = array_merge(static::$errors['rules'][$operator] ?? [], $adhocErr['rules']);
        } else {
            static::$errors['rules'][$operator][] = $instance;
        }

        if (isset($adhocErr['exceptions']) && ! empty($adhocErr['exceptions'])) {
            static::$errors['exceptions'][$operator] = array_merge(static::$errors['exceptions'][$operator] ?? [], $adhocErr['exceptions']);                                
        } else {
            static::$errors['exceptions'][$operator][] = $exception;
        }
	}

	public static function getErrorMessages(): array 
	{
		return static::$errors;
	}

	public static function reset(): void 
	{
		static::$errors = [
			'messages' => [],
	        'rules' => [],
	        'exceptions' => []
		];
	}
}