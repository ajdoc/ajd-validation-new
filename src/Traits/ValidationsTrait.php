<?php 

namespace AjdVal\Traits;

use AjdVal\Contracts\RuleInterface;
use AjdVal\Validators\ValidatorsInterface;
use AjdVal\Errors\ErrorBuilder;
use AjdVal\Errors\Error;
use AjdVal\Errors\ErrorOperatorMessageEnum;
use AjdVal\Expression\ExpressionOperator;

trait ValidationsTrait
{
	protected function operatorErrorMapping(): array 
	{
		return [
			ExpressionOperator::And->value => ErrorOperatorMessageEnum::AND->value,
			ExpressionOperator::Or->value => ErrorOperatorMessageEnum::OR->value,
			ExpressionOperator::Xor->value => ErrorOperatorMessageEnum::XOR->value,
		];
	}

	public function buildRuleError(
		ValidatorsInterface $validator, 
		RuleInterface $rule, 
		string $path,
		mixed $root, 
		mixed $value
	): void {
		$rule->setName($path);

	 	if (! empty($messages = $rule->getAdhocErrors())) {

	 		$key = 0;
	 		
	 		foreach ($messages['messages'] as $operator => $groupedMessages) {

	 			$errorCollection = $validator->buildError();

	 			$errorCollection->setMessage(PHP_EOL.$this->operatorErrorMapping()[$operator] ?? '')->addViolation();

	 			$this->pushErrorMulti(
	 				$groupedMessages,
	 				$key,
	 				$validator,
	 				$rule,
	 				$path,
	 				$root,
	 				$value,
	 				// $errorCollection
	 			);
	 			
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

	protected function pushErrorMulti(
		array $messages,
		int $parentKey,
		ValidatorsInterface $validator, 
		RuleInterface $rule, 
		string $path,
		mixed $root, 
		mixed $value,
		ErrorBuilder|null $parentErrorCollection = null,
	): void 
	{
		$key = 0;

		foreach ($messages as $errorKey => $message) {
			$errorCollection = is_null($parentErrorCollection) ? $validator->buildError() : $parentErrorCollection;
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
	}
}