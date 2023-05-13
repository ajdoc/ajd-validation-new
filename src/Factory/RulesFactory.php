<?php 

namespace AjdVal\Factory;

use AjdVal\Utils;

class RulesFactory extends AbstractFactory
{
	use FactoryExtenderTrait;

	public function initialize()
	{
		$this->setNamespace('AjdVal\\Rules\\')
			->setDirectory(dirname( __DIR__ ).Utils\Utils::DS.'Rules'.Utils\Utils::DS)
			->setFactoryType(FactoryTypeEnum::TYPE_RULES);

		static::append($this);
	}
}