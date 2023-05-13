<?php 

namespace AjdVal\Builder;

use AjdVal\Factory\RulesFactory;
use AjdVal\Factory\RuleExceptionsFactory;
use AjdVal\Validators\DefaultValidator;
use AjdVal\Parsers;

class DefaultValidatorBuilder extends AbstractCompositeValidatorBuilder
{
	public function initialize(): ValidatorBuilderInterface
	{
		DefaultValidator::setSendToExpressionValidator(true);

		return $this->addValidatorBuilder(
						$this->setRulesFactory(new RulesFactory)
							->setRulesExceptionFactory(new RuleExceptionsFactory)
							->setValidatorClass(DefaultValidator::class)
							->addDefaultDoctrineAnnotationReader()
							->addParser(new Parsers\AttributeParser)
					)
					->getValidatorBuilder();
	}
}