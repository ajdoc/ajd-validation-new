<?php 

namespace AjdVal\Errors;

use AjdVal\Contracts\RuleInterface;
use AjdVal\Traits\ErrorsTrait;
use Stringable;

class Error 
{
	use ErrorsTrait;

    public static $errorIndent = ":\n    ";

    public function __construct(
     	private string|Stringable $message, 
     	private ?string $messageTemplate, 
     	private string|null $field = null, 
     	private array $parameters = [], 
     	private mixed $root = null, 
     	private string|null $propertyPath = null, 
     	private mixed $invalidValue = null, 
     	private int|null $plural = null, 
     	private string|null $code = null, 
     	private RuleInterface|null $rule = null, 
     	private mixed $cause = null
     )
    {

    }

    public function __toString(): string
    {
        if (\is_object($this->root)) {
            $class = 'Object('.$this->root::class.')';
        } elseif (\is_array($this->root)) {
            $class = 'Array';
        } else {
            $class = (string) $this->root;
        }

        $propertyPath = (string) $this->propertyPath;

        if ('' !== $propertyPath && '[' !== $propertyPath[0] && '' !== $class) {
            $class .= '.';
        }

        if (null !== ($code = $this->code) && '' !== $code) {
            $code = ' (code '.$code.')';
        }

        if (empty($class) && empty($propertyPath)) {
            
            return static::cleanErrorIndent(static::$errorIndent).$this->getMessage().$code;
        }

        return $class.$propertyPath.static::$errorIndent.$this->getMessage().$code;
    }

    public function getMessageTemplate(): string
    {
        return (string) $this->messageTemplate;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getPlural(): int|null
    {
        return $this->plural;
    }

    public function getMessage(): string|Stringable
    {
        return $this->message;
    }

    public function getRoot(): mixed
    {
        return $this->root;
    }

    public function getPropertyPath(): string
    {
        return (string) $this->propertyPath;
    }

    public function getInvalidValue(): mixed
    {
        return $this->invalidValue;
    }

    public function getRule(): RuleInterface|null
    {
        return $this->rule;
    }

    public function getCause(): mixed
    {
        return $this->cause;
    }

    public function getCode(): string|null
    {
        return $this->code;
    }

     public function getField(): string|null
    {
        return $this->field;
    }
}