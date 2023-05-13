<?php

namespace AjdVal\Context;

class ContextFactory implements ContextFactoryInterface
{
    public function createContext(mixed $root): ContextInterface
    {
        return new Context(
            $root
        );
    }
}