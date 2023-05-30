<?php 

namespace AjdVal\Handlers;

use AjdVal\Contracts\HandlerInterface;
use AjdVal\Contracts\RuleInterface;

abstract class AbstractHandlers implements HandlerInterface
{
	protected $rule;

	public $properties = [];

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getOptions(): array
    {
        return array_values($this->properties);
    }

	public function getName(): string
    {
        return static::class;
    }

    public function preInit(array $arguments = []): array
    {
    	return [];
    }

    public function preCheck(array $details): array
    {
        return [];
    }

    public function postCheck(array|bool $result, array $details): array
    {
        return [];
    }

    public function setRuleObject(RuleInterface $rule): void
    {
    	$this->rule = $rule;
    }

    public function getRuleObject(): RuleInterface
    {
        return $this->rule;
    }

    public function __set($name, $value)
    {
        $this->properties[$name] = $value;
    }

    public function __get($name)
    {
    	if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }

        return null;
    }

    public function __isset($name)
   	{
   		return isset($this->properties[$name]);
   	}

    public function setPreviousHandler(HandlerInterface $handler): void
    {
        $this->previousHandler = $handler;
    }

    public function getPreviousHandler(): HandlerInterface
    {
        return $this->previousHandler;
    }

    public function clearPreviousHandler(): void
    {
        $this->previousHandler = null;
    }
}