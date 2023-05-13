<?php

namespace AjdVal\Context;

interface ContextFactoryInterface
{
    /**
     * Creates the context.
     *
     * @param  mixed  $root
     * @return \AjdVal\Context\ContextFactoryInterface
     */
    public function createContext(mixed $root): ContextInterface;
}