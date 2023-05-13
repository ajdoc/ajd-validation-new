<?php 

namespace AjdVal\Factory;

use AjdVal\Utils;

class RuleExceptionsFactory extends AbstractFactory
{
	use FactoryExtenderTrait;

	public function initialize()
	{
		$this->setNamespace('AjdVal\\Exceptions\\')
			->setDirectory(dirname( __DIR__ ).Utils\Utils::DS.'Exceptions'.Utils\Utils::DS)
			->setFactoryType(FactoryTypeEnum::TYPE_RULE_EXCEPTIONS)
			->allowNotFound();

		static::append($this);
	}
}