<?php 

namespace AjdVal\Rules;

use AjdVal\Contracts;
use AjdVal\Builder;
use AjdVal\Factory;
use AjdVal\Context;
use AjdVal\Exceptions;
use AjdVal\Traits;
use AjdVal\Utils\Utils;
use AjDic\AjDic;
use AjdVal\ValidatorDto;
use AjdVal\Expression;
use AjdVal\Exceptions\ValidationExceptions;
use AjdVal\Errors\ErrorCollection;
use AjdVal\Errors\Error;
use AjdVal\Errors\ErrorBuilder;
use AjdVal\Validators\ValidatorsInterface;
use AjdVal\Handlers\HandlerDto;
use AjdVal\Traits\ValidatorsTrait;

use Stringable;
use RuntimeException;
use InvalidArgumentException;

abstract class AbstractRule implements Contracts\RuleInterface
{
	use Traits\CanHandlerStack;
	use Traits\RuleCreatorTrait;

	public static RuleHandlerStrategy $ruleHandlerStrategy = RuleHandlerStrategy::AutoCreate;

	protected Context\ContextFactoryInterface $contextFactory;
	protected Context\ContextInterface $context;
	protected ValidatorDto $ruleValidatorDto;

	protected ErrorCollection $errorCollection;

	protected Expression\ExpressionDefinition $expressionCreated;
	protected ValidatorsInterface $validator;

	protected string $name;
	protected array $adhocErrors = [];

	protected array $passedArguments = [];

	public function __construct(HandlerDto|null $handler = null)
    {
        $this->initHandler($handler, func_get_args());

        $this->errorCollection = new ErrorCollection;
    }

    public function initHandler(HandlerDto|null $handler = null, array $arguments = []) 
    {
    	if (! is_null($handler)) {
			$this->setErrorMessage($handler?->message ?? '');
			$this->setArguments($handler->getOptions());
		} else {
			$this->setArguments($arguments);
		}
    }

    public function setArguments(array $arguments): void
    {
    	$this->passedArguments = $arguments;
    }

    public function getArguments(): array
    {
    	return $this->passedArguments;
    }

    public function setValidator(ValidatorsInterface $validator): void
    {
    	$this->validator = $validator;
    }

    public function getValidator(): ValidatorsInterface
    {
    	return $this->validator;
    }

    public function getValidationClass(): string
    {
    	return $this->ruleValidatorDto->getValidatorBuilder()->getValidationClass();
    }

    public function setExpressionDefinition(Expression\ExpressionDefinition $expressionCreated): void
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

    public function setErrorMessage(string|Stringable $message): static
    {
    	if (empty($message)) {
    		return $this;
    	}

    	return $this->setAdhocErrors([
    		'messages' => [$message],
    		'rules' => [$this]
    	]);
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
    	if (! isset($this->adhocErrors['messages']) || empty($this->adhocErrors['messages'])) {
    		return '';
    	}

    	if (empty($separator)) {
    		$separator = PHP_EOL.Error::cleanErrorIndent(Error::$errorIndent);
    	}

    	return implode($separator, $this->adhocErrors['messages']);
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

    	$exception = $exceptionsFactory->generate(Utils::getShortNameClass($class), null, ...$extraParams);

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