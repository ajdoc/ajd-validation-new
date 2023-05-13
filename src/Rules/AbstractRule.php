<?php 

namespace AjdVal\Rules;

use AjdVal\Contracts;
use AjdVal\Builder;
use AjdVal\Factory;
use AjdVal\Context;
use AjdVal\Exceptions;
use AjDic\AjDic;
use AjdVal\ValidatorDto;
use AjdVal\Expression;
use AjdVal\Exceptions\ValidationExceptions;
use AjdVal\Errors\ErrorCollection;
use AjdVal\Errors\Error;
use AjdVal\Errors\ErrorBuilder;
use RuntimeException;
use InvalidArgumentException;

abstract class AbstractRule implements Contracts\RuleInterface
{
	protected Context\ContextFactoryInterface $contextFactory;
	protected Context\ContextInterface $context;
	protected ValidatorDto $ruleValidatorDto;

	protected ErrorCollection $errorCollection;

	protected Expression\ExpressionDefinition $expressionCreated;

	protected string $name;
	protected array $adhocErrors = [];

	public function __construct(mixed $options = null)
    {
    	$options = $this->normalizeOptions($options);

    	foreach ($options as $name => $value) {
        	$this->$name = $value;
        }

        $this->errorCollection = new ErrorCollection;
    }

    public function setExpressionDefinition(Expression\ExpressionDefinition $expressionCreated)
    {
    	$this->expressionCreated = $expressionCreated;
    }

    public function getExpressionDefinition(): Expression\ExpressionDefinition
    {
    	return $this->expressionCreated;
    }

    public function getErrors(): ErrorCollection
    {
    	return $this->errorCollection;
    }

    public function setAdhocErrors(array $errors): static
    {
    	$this->adhocErrors = $errors;

    	return $this;
    }

    public function getAdhocErrors(): array 
    {
    	return $this->adhocErrors;
    }

    public function formatAdhocError(string $separator = ''): string 
    {
    	if (empty($this->adhocErrors)) {
    		return '';
    	}

    	if (empty($separator)) {
    		$errorIndent = str_replace([':'], [''], Error::$errorIndent);
    		$errorIndent = preg_replace('/[\n]/', '', $errorIndent);
    		$separator = PHP_EOL.$errorIndent;
    	}

    	return implode($separator, $this->adhocErrors);
    }

    public function buildError()
    {
    	return new ErrorBuilder($this->errorCollection);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

	public function getDefaultOption()
    {
        return null;
    }

    public function getRequiredOptions()
    {
        return [];
    }

    protected function normalizeOptions(mixed $options): array
    {
        $normalizedOptions = [];
        $defaultOption = $this->getDefaultOption();
        $invalidOptions = [];
        $missingOptions = array_flip((array) $this->getRequiredOptions());
        $knownOptions = get_class_vars(static::class);

        if (\is_array($options) && isset($options['value']) && !property_exists($this, 'value')) {
            if (null === $defaultOption) {
                throw new InvalidArgumentException(sprintf('No default option is configured for rule "%s".', static::class));
            }

            $options[$defaultOption] = $options['value'];
            unset($options['value']);
        }

        if (\is_array($options)) {
            reset($options);
        }
        if ($options && \is_array($options) && \is_string(key($options))) {
            foreach ($options as $option => $value) {
                if (\array_key_exists($option, $knownOptions)) {
                    $normalizedOptions[$option] = $value;
                    unset($missingOptions[$option]);
                } else {
                    $invalidOptions[] = $option;
                }
            }
        } elseif (null !== $options && !(\is_array($options) && 0 === \count($options))) {

        	if (is_array($options) && is_numeric(key($options))) {
        		$knownOptionsTolist = array_values($knownOptions);
        		$knownOptionsTolist = array_combine(array_keys($knownOptionsTolist), array_keys($knownOptions));
        		
        		foreach ($options as $key => $value) {
        			if (\array_key_exists($key, $knownOptionsTolist)) {
	                    $normalizedOptions[$knownOptionsTolist[$key]] = $value;
	                    unset($missingOptions[$knownOptionsTolist[$key]]);
	                } else {
	                    $invalidOptions[] = $knownOptionsTolist[$key];
	                }
        		}

        	} else {

	            if (null === $defaultOption) {
	                throw new InvalidArgumentException(sprintf('No default option is configured for rule "%s".', static::class));
	            }

	             if (\array_key_exists($defaultOption, $knownOptions)) {
	                $normalizedOptions[$defaultOption] = $options;
	                unset($missingOptions[$defaultOption]);
	            } else {
	                $invalidOptions[] = $defaultOption;
	            }
	        }
        }

        if (\count($invalidOptions) > 0) {
            throw new InvalidArgumentException(sprintf('The options "%s" do not exist in rule "%s".', implode('", "', $invalidOptions), static::class), $invalidOptions);
        }

        if (\count($missingOptions) > 0) {
            throw new InvalidArgumentException(sprintf('The options "%s" must be set for rule "%s".', implode('", "', array_keys($missingOptions)), static::class), array_keys($missingOptions));
        }

        return $normalizedOptions;
    }
	 
    public function setContextFactory(Context\ContextFactoryInterface $contextFactory): void
    {
    	$this->contextFactory = $contextFactory;
    }

    public function setContext(Context\ContextInterface $context): void
    {
    	$this->context = $context;
    }

    public function getContextFactory(): Context\ContextFactoryInterface
    {
    	return $this->contextFactory;
    }

    public function getContext(): Context\ContextInterface
    {
    	return $this->context;
    }

    public function setValidatorDto(ValidatorDto $validatorDto): void
    {
    	$this->ruleValidatorDto = $validatorDto;
    }

    protected function normalizeArgRules(Contracts\RuleInterface|array|null $rules): array 
    {
    	if (method_exists($this, 'getRules')) {
    		$allRules = $this->getRules();
    	} else if(is_null($rules)) {
    		$allRules = [$this];
    	}

    	if (! is_null($rules)) {
    		$allRules = $rules;
    	}

    	if (! is_array($allRules)) {
			$allRules = [$allRules];
		}

		return $allRules;
    }

    public function getRuleExceptionMessage(Contracts\RuleInterface|null $rule = null): string
    {
    	$rule = $this->normalizeArgRules($rule);
    	$messages = $this->getRuleExceptionMessages($rule);

    	return $messages['messages'][0] ?? '';
    }

    public function getRuleExceptionMessages(Contracts\RuleInterface|array|null $rules = null): array
    {
    	$rules = $this->normalizeArgRules($rules);
    	$exceptions = $this->getAllThrownExceptions(null, $rules, true);

    	$messages = [
    		'messages' => []
    	];

    	foreach ($exceptions as $exception) {
    		if ($exception instanceof Exceptions\NestedExceptions) {
    			$messages['messages'][] = $exception->getFullMessage();
    		} else if ($exception instanceof Exceptions\ValidationExceptions) {
    			$messages['messages'][] = $exception->getExceptionMessage();
    		}	
    	}

    	return $messages;
    }

    public function getRuleException(Contracts\RuleInterface|array|null $rules): array
    {
    	return $this->getAllThrownExceptions(null, $rules, true);
    }

    public function getAllThrownExceptions(mixed $value, Contracts\RuleInterface|array|null $rules, bool $forceAssert = false): array
    {
    	$allRules = $this->normalizeArgRules($rules);

        return array_filter(
            array_map(
                function (Contracts\RuleInterface $rule) use ($value, $forceAssert): ?ValidationExceptions {
                    try {
                    	$rule->setValidatorDto($this->ruleValidatorDto);
                        $rule->assert($value, null, $forceAssert);
                    } catch (ValidationExceptions $exception) {
                        $this->updateExceptionTemplate($exception);

                        return $exception;
                    }

                    return null;
                },
                $allRules
            )
        );
    }

    public function assert(mixed $value, Contracts\RuleInterface|array|null $rules = null, bool $forceAssert = false): void
    {
        if ($this->validate($value) && !$forceAssert) {
            return;
        }

        $exception = $this->reportError($value);

        if (is_null($exception)) {
        	return;
        }

        throw $exception;
    }

    public function reportError($value, array $extraParams = [], Contracts\RuleInterface|null $rule = null): Contracts\ExceptionInterface|null
    {
    	$reportRule = ! is_null($rule) ? $rule : $this;
    	$class = get_class($reportRule);

    	$validatorBuilder = $this->ruleValidatorDto->getValidatorBuilder();
    	$exceptionsFactory = $validatorBuilder->getRulesExceptionFactory();

    	$exception = $exceptionsFactory->generate($this->getShortNameClass($class), null, ...$extraParams);

    	if (is_null($exception)) {
    		return null;
    	}

    	$name = $this->name ?? Error::stringify($value);

    	if ($name === 'null') {
    		$name = 'value';
    	}

    	$params = array_merge(
            get_class_vars($class),
            get_object_vars($reportRule),
            $extraParams,
            compact('value')
        );

        $params['field'] = $name;

        $exception->configure($params);
        $exception->setName($name);

    	return $exception;

    }

    private function getShortNameClass($class, Factory\FactoryTypeEnum $suffix = Factory\FactoryTypeEnum::TYPE_RULES)
    {
    	$segments = \explode('\\', $class);
    	return \strtolower(\str_replace([$suffix->value], [''], \end($segments)));
    }

    private function updateExceptionTemplate(ValidationExceptions $exception): void
    {
       /* if ($this->template === null || $exception->hasCustomTemplate()) {
            return;
        }

        $exception->updateTemplate($this->template);

        if (!$exception instanceof NestedValidationException) {
            return;
        }

        foreach ($exception->getChildren() as $childException) {
            $this->updateExceptionTemplate($childException);
        }*/
    }
}