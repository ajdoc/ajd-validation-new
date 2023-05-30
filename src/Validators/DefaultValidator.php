<?php 

namespace AjdVal\Validators;

use AjdVal\Rules;
use AjdVal\Contracts;
use AjdVal\Traits;
use AjDic\AjDic;
use AjdVal\ValidatorDto;
use AjdVal\Context;
use AjdVal\Validations;
use AjdVal\Expression;
use AjdVal\Expression\ExpressionBuilderValidator;

use LogicException;

class DefaultValidator extends Rules\AllRule implements ValidatorsInterface, CanBeExpressiveInterface
{
	use Traits\ValidatorsTrait;
	use Traits\ValidatorExtenderTrait;

	protected static ValidatorDto|null $validatorDto = null;
	protected static bool $sendToExpressionValidator = false;

	public function __construct(protected Context\ContextFactoryInterface $contextFactory)
	{
		parent::__construct();
		
		$this->setValidatorDto(static::getValidatorDto());
		$this->setValidator($this);
	}

    public static function setSendToExpressionValidator(bool $send): void
    {
    	static::$sendToExpressionValidator = $send;
    }

    public static function getSendToExpressionValidator(): bool
    {
    	return static::$sendToExpressionValidator;
    }

	public static function create(Context\ContextFactoryInterface|null $contextFactory = null): static|ExpressionBuilderValidator
    {
    	$conFact = ! empty($contextFactory) ? $contextFactory : new Context\ContextFactory;

    	$instance = new static($conFact);

    	if (static::$sendToExpressionValidator) {
    		return $instance->expr();
    	}

        return $instance;
    }

	public static function withValidatorDto(ValidatorDto $validatorDto): static|ExpressionBuilderValidator
	{
		static::$validatorDto = $validatorDto;

		return static::create();
	}

	public static function getValidatorDto(): ValidatorDto
	{
		if (empty(static::$validatorDto)) {
			static::$validatorDto = new ValidatorDto;
		}

		if (is_null(static::$validatorDto->getValidatorBuilder()->getRulesFactory())) {
			throw new RuntimeException('There is no Rules Factory set.');
		}

		return static::$validatorDto;
	}

	public static function addValidatorBuilder(Builder\ValidatorBuilderInterface $validatorBuilder): static|ExpressionBuilderValidator
	{
		static::$validatorDto = static::getValidatorDto()->addValidatorBuilder($validatorBuilder);

		return static::create();

	}

	public static function __callStatic(string $ruleName, array $arguments): static
    {
        return self::create()->__call($ruleName, $arguments);
    }

    public function __call(string $rule, array $arguments): static
    {
    	if (! method_exists($this, $rule)) {
	    	$rule = static::buildRule($rule, $arguments);
	    	$rule->setContextFactory($this->contextFactory);

	        return $this->addRuleValidator($rule);
	     } 

	     return $this;
    }

    public function getValidator(): ValidatorsInterface
    {
    	return $this;
    }

    public static function buildRule(string $rule, array $arguments = []): mixed
   	{
   		return static::processRules($rule, $arguments);
   	}

	protected static function processRules(string $rule, array $arguments = []): mixed
   	{
   		$validatorDto = static::getValidatorDto();

   		$resovler = static::resolveRule($validatorDto->getValidatorBuilder(), $rule, $arguments);

   		/*echo '<pre>';
   		print_r($validatorDto->getValidatorBuilder()->getRulesFactory()->getNamespaces());*/

   		$rule = $validatorDto->getValidatorBuilder()->getRulesFactory()->generate($rule, $resovler, ...$arguments);
   		
   		$rule->setValidatorDto(static::getValidatorDto());
   		
   		return $rule;
   	}
}