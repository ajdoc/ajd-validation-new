<?php

namespace AjdVal\Traits;

use AjdVal\Handlers\AbstractHandlers;
use AjdVal\Contracts\RuleInterface;
use AjdVal\Contracts\HandlerInterface;
use AjdVal\Handlers\HandlerDto;
use AjdVal\Rules\RuleHandlerStrategy;

trait CanHandlerStack
{
    protected array $ruleHandlers = [];
    protected HandlerDto $handlerDto;

    protected static array $handlerStack = [];
    protected static array $staticHandlerStack = [];

    public static function setRuleHandlerStack(): void
    {

    }

    public static function setHandlerStack(array $handlerStack): void
    {
        $static = new static;
        $name = get_class($static);

        static::$staticHandlerStack[$name] = $handlerStack;
    }

    public static function flushHandlerStack(): void
    {
        static::$handlerStack = [];
    }

    public static function getHandlerStack(): array
    {
        $name = static::class;
        
        if (isset(static::$staticHandlerStack[$name]) && ! empty(static::$staticHandlerStack[$name])) {
            return static::$staticHandlerStack[$name];
        }
        
        return static::$handlerStack;
    }

    public static function processHandlerPreInit(array $arguments, string $className, array $handlerStack = [], RuleInterface|null $rule = null): array
    {
        $handlers = $handlerStack;

        if (empty($handlers) && null !== $rule) {
            $handlers = $rule->getRuleHandlers();
        }

        $newArgs = [];

        if (empty($handlers)) {

            if (empty($arguments) || $className::$ruleHandlerStrategy == RuleHandlerStrategy::NotAutoCreate) {
                return $arguments;
            }

            $handlerDto = new HandlerDto;

            foreach ($arguments as $key => $argument) {
                if (is_string($argument) && $key === 0) {
                    $handlerDto->message = $argument;
                }

                $handlerDto->{'arg'.$key} = $argument;
            }

            return [$handlerDto];
        }

        foreach ($handlers as $handlerKey => $handlerObjPer) {
            if (isset($handlerArr[$handlerKey - 1])) {
                $handlerObjPer->setPreviousHandler($handlerArr[$handlerKey - 1]);
            }

            $initArguments = $handlerObjPer->preInit($arguments);

            if (is_array($initArguments)) {
                $newArgs = array_merge($newArgs, $initArguments);
            }
        }

        if (empty($newArgs)) {
            $handlerProps = static::setHandlerDtoProperties($handlers);

            if (!empty($handlerProps)) {
                $handlerDto = new HandlerDto;

                foreach ($handlerProps as $propName => $propValue) {
                    $handlerDto->{$propName} = $propValue;
                }
                
                $newArgs[] = $handlerDto;
            }
        }

        return $newArgs;
    }

    public function processHandlerPreCheck(RuleInterface $rule, array $details, array $handlerStack = []): array
    {
        $handlers = $handlerStack;

        if (empty($handlers)) {
            $handlers = $rule->getRuleHandlers();
        }

        foreach ($handlers as $handlerKey => $handlerObjPer) {

            if (isset($handlerArr[$handlerKey - 1])) {
                            
                $handlerObjPer->setPreviousHandler($handlerArr[$handlerKey - 1]);
            }

            $preCheckValue = $handlerObjPer->preCheck($details);

            if (is_array($preCheckValue)) {
                $details = array_merge($details, $preCheckValue);
            }
        }

        return $details;
    }

    public function processHandlerPostCheck(
        RuleInterface $rule, 
        array $details, 
        array|bool $check_r, 
        array $handlerStack = [], 
        bool $clear = false
    ): array|bool {

        $handlers = $handlerStack;

        if (empty($handlers)) {
            $handlers = $rule->getRuleHandlers();
        }

        foreach ($handlers as $handlerKey => $handlerObjPer) {

            if (isset($handlerArr[$handlerKey - 1])) {
                            
                $handlerObjPer->setPreviousHandler($handlerArr[$handlerKey - 1]);
            }

            $postCheckValue = $handlerObjPer->postCheck($check_r, $details);

            if (is_array($check_r)) {
                $check_r = array_merge($check_r, $postCheckValue);  
            } else {
                $check_r = $postCheckValue['check'] ?? $check_r ?? false;
            }
        }

        if ($clear) {
           static::resetHandlers($rule); 
       }

        return $check_r;
    }

    public static function resetHandlers(RuleInterface $rule)
    {
        AbstractHandlers::clearPreviousHandler();

        if ($rule) {
            $rule->clearRuleHandlers();
            $rule->clearHandlerDto();
            $rule->flushHandlerStack();
        }
    }

    public function setRuleHandlers(array $handlers): void
    {
        $this->ruleHandlers = $handlers;
    }

    public function getRuleHandlers(): array
    {
        return $this->ruleHandlers;
    }

    public function getRuleHandlersByName(string $handlerName): ?HandlerInterface
    {
        if (empty($this->ruleHandlers)) {
            return null;
        }

        foreach ($this->ruleHandlers as $ruleHandler) {
            if( get_class($ruleHandler) == $handlerName ) {
                return $ruleHandler;
            }
        }

        return null;
    }

    public function clearRuleHandlers(): void
    {
        $this->ruleHandlers = [];
    }

    public static function setHandlerDtoProperties(array $handlers): array
    {
        $handlerProperties = [];

        foreach ($handlers as $handler) {
            if (!isset($handler->properties)) {
                continue;
            }
            
            foreach ($handler->properties as $propName => $propValue) {
                $handlerProperties[$propName] = $propValue;
            }
        }

        return $handlerProperties;
    }

    public function setHandlerDto(array $handlers = [], $handlerDto = null): void
    {
        if (! empty($handlerDto) && $handlerDto instanceof HandlerDto) {
            $this->handlerDto = $handlerDto;
            return;
        }

        if (empty($handlers)) {
            $handlers = $this->ruleHandlers;
        }

        if (empty($handlers)) {
            return;
        }

        $handlerProperties = static::setHandlerDtoProperties($handlers);
        
        if (!empty($handlerProperties)) {

            $handlerDto = new HandlerDto;

            foreach ($handlerProperties as $propName => $propValue) {
                $handlerDto->{$propName} = $propValue;
            }

            $this->handlerDto = $handlerDto;
        }
    }

    public function getRuleHandler(): HandlerDto
    {
        return $this->getHandlerDto();
    }

    public function getHandlerDto(): HandlerDto
    {
        return $this->handlerDto;
    }

    public function clearHandlerDto(): void
    {
        $this->handlerDto = null;
    }
}