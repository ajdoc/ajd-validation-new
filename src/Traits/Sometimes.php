<?php
namespace AjdVal\Traits;

use AjdVal\Contracts\RuleInterface;

trait Sometimes
{
	protected array $sometimes = [];

	public function sometimes(string|bool|callable|null $condition = null): static
	{
		$ruleCount = $this->ruleCount - 1;
		if (isset($this->ruleInstances[$ruleCount])) {
			$rule = $this->ruleInstances[$ruleCount];
			$this->sometimes[$ruleCount][$rule->getName().''.$ruleCount] = $condition;
		}

		return $this;
	}

	public function on(string|array|callable $scenario): static
	{
		$callback = function(mixed $value, RuleInterface $obj, RuleInterface|null $currentRule = null) use ($scenario): bool {

			$validator = $obj->getValidator();
			$triggers = $validator->getTriggerState();

			if (is_callable($scenario)) {

				return (bool) $scenario(...[$triggers, ...func_get_args()]);
			}

			$scenarios = is_string($scenario) ? [$scenario] : $scenario;

			foreach ($scenarios as $scenario) {
				if (in_array($scenario, $triggers)) {
					return true;
				}
			}

			return false;
		};

		return $this->sometimes($callback);
	}
}