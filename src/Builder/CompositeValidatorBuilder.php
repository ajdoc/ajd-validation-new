<?php 

namespace AjdVal\Builder;

use AjdVal\Factory\RulesFactory;

class CompositeValidatorBuilder extends AbstractCompositeValidatorBuilder
{
	public function initialize(): ValidatorBuilderInterface
	{
		return $this->getValidatorBuilder();
	}
}