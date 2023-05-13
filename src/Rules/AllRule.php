<?php 

namespace AjdVal\Rules;

use AjdVal\Contracts;
use AjdVal\Validations;

class AllRule extends AbstractAll
{
	public function validate(mixed $value, string $path = ''): bool
	{
		$rules = $this->getRules();

		if (empty($rules) && ! \is_object($value)) {
			return true;
		}

		$this->setContext($this->getContextFactory()->createContext(['value' => $value, 'root' => $this]));

		$validation = new Validations\DefaultValidation($this->getContext(), $this->ruleValidatorDto->getMetadataFactory(), $this->ruleValidatorDto);

		if ('' !== $path) {
			$validation->filterPath($path);
		}

		$result = $validation->validate($value, $rules);

		$this->removeRules();

		return $result;
	}

	public function assert(mixed $value, Contracts\RuleInterface|array|null $rules = null, bool $forceAssert = false): void
    {
    	$allRules = $this->normalizeArgRules($rules);

        $exceptions = $this->getAllThrownExceptions($value);
        $numRules = count($allRules);
        $numExceptions = count($exceptions);
        $summary = [
            'total' => $numRules,
            'failed' => $numExceptions,
            'passed' => $numRules - $numExceptions,
        ];

        if (!empty($exceptions)) {
            /** @var AllOfException $allOfException */
            $allOfException = $this->reportError($value, $summary);
            $allOfException->addChildren($exceptions);

            throw $allOfException;
        }
    }
}