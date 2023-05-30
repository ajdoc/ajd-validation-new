<?php 

namespace AjdVal\Traits;

use AjdVal\Contracts\RuleInterface;
use AjdVal\Validators\ValidatorsInterface;
use AjdVal\Errors\ErrorBuilder;
use AjdVal\Errors\Error;

trait ValidationsTrait
{
	public function buildRuleError(
		ValidatorsInterface $validator, 
		RuleInterface $rule, 
		string $path,
		mixed $root, 
		mixed $value
	) {
		$rule->setName($path);

	 	if (! empty($messages = $rule->getAdhocErrors())) {
	 		$key = 0;
	 		
	 		foreach ($messages['messages'] as $errorKey => $message) {
	 			
	 			$errorCollection = $validator->buildError();
	 			$rule = $messages['rules'][$errorKey] ?? $rule;
	 			$exception = $messages['exceptions'][$errorKey] ?? null;

	 			$params = array_merge(
	 				[
	 					'path' => $path,
	 					'root' => $root,
	 					'value' => $value,
	 					'field' => $path
	 				], 
	 				$exception?->getParams() ?? []);

	 			$message = Error::replaceErrorPlaceholder($params, $message);

	 			if ($key === 0) {
	 				$errorCollection->atPath($path)->setRoot($root)->setInvalidValue($value)->setRule($rule);
	 			}

	 			$errorCollection->setMessage($message)->addViolation();

	 			$key++;
	 		}
            
        } else {

        	$errorCollection = $validator->buildError()
				->atPath($path)	
				->setRoot($root)
				->setInvalidValue($value)
				->setRule($rule)
				->setMessage($rule->getRuleExceptionMessage())
				->addViolation();
        }
	}
}