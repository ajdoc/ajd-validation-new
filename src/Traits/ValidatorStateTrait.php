<?php 

namespace AjdVal\Traits;

trait ValidatorStateTrait
{
	protected static array $triggerState = [];

	public static function trigger(array|string $triggers): void
	{
		$triggers = is_string($triggers) ? [$triggers] : $triggers;

		static::$triggerState = $triggers;
	}

	public static function getTriggerState(): array 
	{
		return static::$triggerState;
	}
}