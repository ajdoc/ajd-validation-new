<?php 

namespace AjdVal\Traits;

use AjdVal\Factory;

trait ValidatorExtenderTrait
{
	public static function addRulesLookup(string $namespace, string $directory): self
	{
		Factory\RulesFactory::addNamespace($namespace);
		Factory\RulesFactory::addDirectory($directory);

		return self::create();
	}

	public static function addRuleExceptionsLookup(string $namespace, string $directory): self
	{
		Factory\RulesFactory::RuleExceptionsFactory($namespace);
		Factory\RulesFactory::RuleExceptionsFactory($directory);

		return self::create();
	}
}