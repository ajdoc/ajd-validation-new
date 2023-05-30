<?php 

namespace AjdVal\Factory;

use AjdVal\Utils;

class RuleHandlersFactory extends AbstractFactory
{
	use FactoryExtenderTrait;

	public function initialize()
	{
		$this->setNamespace('AjdVal\\Handlers\\')
			->setDirectory(dirname( __DIR__ ).Utils\Utils::DS.'Handlers'.Utils\Utils::DS)
			->setFactoryType(FactoryTypeEnum::TYPE_RULE_HANDLERS)
			->allowNotFound();

		static::append($this);
	}
}