<?php 

namespace AjdVal\Exceptions;

use Countable;
use RecursiveIterator;

class RecursiveExceptions implements RecursiveIterator, Countable
{
    private $exceptions;

    public function __construct(NestedExceptions $parent)
    {
        $this->exceptions = $parent->getRelated();
    }

    public function count()
    {
        return $this->exceptions->count();
    }

    public function hasChildren()
    {
        if (!$this->valid()) {
            return false;
        }

        return ($this->current() instanceof NestedExceptions);
    }

    public function getChildren() : RecursiveIterator
    {
        return new static($this->current());
    }

    public function current()
    {
        return $this->exceptions->current();
    }

    public function key()
    {
        return $this->exceptions->key();
    }

    public function next()
    {
        $this->exceptions->next();
    }

    public function rewind()
    {
        $this->exceptions->rewind();
    }

    public function valid()
    {
        return $this->exceptions->valid();
    }
}
