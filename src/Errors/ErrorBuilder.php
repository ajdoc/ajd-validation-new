<?php 

namespace AjdVal\Errors;

use AjdVal\Contracts;
use AjdVal\Utils\Utils;
use Stringable;

class ErrorBuilder
{
	 public function __construct(
	 	private ErrorCollection $errorCollection, 
	 	private string|Stringable|null $message = null,
	 	private array $parameters = [],
	 	private string|null $field = null,
	 	private Contracts\RuleInterface|null $rule = null, 
	 	private mixed $root = null, 
	 	private string|null $propertyPath = null, 
	 	private mixed $invalidValue = null,
	 	private int|null $plural = null,
	 	private string|null $code  = null,
	 	private mixed $cause   = null
	) {

    }

    public function atPath(string $path): static
    {
        $this->propertyPath = Utils::appendPropertyPath($this->propertyPath ?? '', $path);

        return $this;
    }

    public function setMessage(Stringable|string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function setParameter(string $key, string $value): static
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function setInvalidValue(mixed $invalidValue): static
    {
        $this->invalidValue = $invalidValue;

        return $this;
    }

    public function setPlural(int $number): static
    {
        $this->plural = $number;

        return $this;
    }

     public function setField(string $field): static
    {
        $this->field = $field;

        return $this;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function setCause(mixed $cause): static
    {
        $this->cause = $cause;

        return $this;
    }

    public function setRule(Contracts\RuleInterface $rule): static
    {
    	$this->rule = $rule;

    	return $this;
    }

     public function setRoot(mixed $root): static
    {
    	$this->root = $root;

    	return $this;
    }

    public function addViolation(): void
    {

        $this->errorCollection->add(new Error(
            $this->message,
            '',
            $this->field,
            $this->parameters,
            $this->root,
            $this->propertyPath,
            $this->invalidValue,
            $this->plural,
            $this->code,
            $this->rule,
            $this->cause
        ));
    }
}