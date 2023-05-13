<?php 

namespace AjdVal\Errors;

use OutOfBoundsException;
use IteratorAggregate;
use ArrayIterator;

class ErrorCollection implements IteratorAggregate
{
	private array $errors = [];

	public function __construct(iterable $errors = [])
    {
        foreach ($errors as $error) {
            $this->add($error);
        }
    }

    public static function createFromMessage(string $message): self
    {
        $self = new self();
        $self->add(new Error($message, '', null, [], null, '', null));

        return $self;
    }

    public function __toString(): string
    {
        $string = '';

        foreach ($this->errors as $error) {
            $string .= $error."\n";
        }

        return $string;
    }

    public function add(Error $error)
    {
        $this->errors[] = $error;
    }

    public function addAll(ErrorCollection $errorCollection)
    {
        foreach ($errorCollection as $error) {
            $this->errors[] = $error;
        }
    }

    public function get(int $offset): Error
    {
        if (!isset($this->errors[$offset])) {
            throw new OutOfBoundsException(sprintf('The offset "%s" does not exist.', $offset));
        }

        return $this->errors[$offset];
    }

    public function has(int $offset): bool
    {
        return isset($this->errors[$offset]);
    }

    public function set(int $offset, Error $error)
    {
        $this->errors[$offset] = $error;
    }

    public function remove(int $offset)
    {
        unset($this->errors[$offset]);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->errors);
    }


    public function count(): int
    {
        return \count($this->errors);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): Error
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $error): void
    {
        if (null === $offset) {
            $this->add($error);
        } else {
            $this->set($offset, $error);
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }
}