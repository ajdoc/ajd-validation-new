<?php 

namespace AjdVal\Builder;

use AjdVal\Factory\RulesFactory;
use AjdVal\Factory\RuleExceptionsFactory;
use AjdVal\Factory\RuleHandlersFactory;
use AjdVal\Validators\DefaultValidator;
use AjdVal\Validations\DefaultValidation;
use AjdVal\Parsers;

class DefaultValidatorBuilder extends AbstractCompositeValidatorBuilder
{
	public function initialize(): ValidatorBuilderInterface
	{
		DefaultValidator::setSendToExpressionValidator(true);

		return $this->addValidatorBuilder(
						$this->setRulesFactory(new RulesFactory)
							->setRulesExceptionFactory(new RuleExceptionsFactory)
							->setRulesHandlerFactory(new RuleHandlersFactory)
							->setValidatorClass(DefaultValidator::class)
							->setValidationClass(DefaultValidation::class)
							->addParser(new Parsers\AttributeParser)
					)
					->getValidatorBuilder();
	}
}