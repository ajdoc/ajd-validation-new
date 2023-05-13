<?php

namespace AjdVal\Parsers\Metadata;

final class TraversalStrategy
{
    /**
     * Specifies that a node's value should be iterated only if it is an
     */
    public const IMPLICIT = 1;

    /**
     * Specifies that a node's value should never be iterated.
     */
    public const NONE = 2;

    /**
     * Specifies that a node's value should always be iterated. If not an exception should be thrown.
     */
    public const TRAVERSE = 4;

    /**
     * Not instantiable.
     */
    private function __construct()
    {
    }
}