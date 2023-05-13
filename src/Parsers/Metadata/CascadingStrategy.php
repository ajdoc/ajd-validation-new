<?php

namespace AjdVal\Parsers\Metadata;

final class CascadingStrategy
{
    /**
     * Specifies that a node should not be cascaded.
     */
    public const NONE = 1;

    /**
     * Specifies that a node should be cascaded.
     */
    public const CASCADE = 2;

    /**
     * Not instantiable.
     */
    private function __construct()
    {
    }
}