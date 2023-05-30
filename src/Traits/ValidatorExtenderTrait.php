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
		Factory\RuleExceptionsFactory::addNamespace($namespace);
		Factory\RuleExceptionsFactory::addDirectory($directory);

		return self::create();
	}

	public static function addRuleHandlersLookup(string $namespace, string $directory): self
	{
		Factory\RuleHandlersFactory::addNamespace($namespace);
		Factory\RuleHandlersFactory::addDirectory($directory);

		return self::create();
	}
}