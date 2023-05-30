<?php 

namespace AjdVal\Rules;
use AjdVal\Rules\AbstractRule;

use Exception;

abstract class AbstractCallback extends AbstractRule
{
	public $callback;
    public $arguments = [];

    public function __construct($callback, ...$arguments)
    {
        if (! is_callable($callback)) {
            throw new Exception('Invalid callback.');
        }

        $this->callback = $callback;
        $this->arguments = $arguments;

        $this->setArguments($this->arguments);
    }

    public function validate(mixed $value, string $path = ''): bool
    {
    	$args = $this->arguments;
    	array_unshift($args, $value);
    	
    	$check = call_user_func_array($this->callback, $args);

    	return $check;
    }
}