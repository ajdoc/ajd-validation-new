<?php

namespace AjdVal\Parsers\Metadata;

final class AutoMappingStrategy
{
    /**
     * Nothing explicitly set, rely on auto-mapping configured regex.
     */
    public const NONE = 0;

    /**
     * Explicitly enabled.
     */
    public const ENABLED = 1;

    /**
     * Explicitly disabled.
     */
    public const DISABLED = 2;

    /**
     * Not instantiable.
     */
    private function __construct()
    {
    }
}