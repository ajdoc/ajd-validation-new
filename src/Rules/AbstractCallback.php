<?php 

namespace AjdVal\Rules;
use AjdVal\Rules\AbstractRule;

abstract class AbstractCallback extends AbstractRule
{
	public $callback;
    public $arguments = [];

    public function __construct($callback)
    {
        if (! is_callable($callback)) {
            throw new Exception('Invalid callback.');
        }

        $this->callback = $callback;
    }

    public function validate(mixed $value, string $path = ''): bool
    {
    	$args = $this->arguments;
    	array_unshift($args, $value);
    	
    	$check = call_user_func_array($this->callback, $args);

    	return $check;
    }
}