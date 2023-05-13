<?php

namespace AjdVal\Parsers\Metadata;

interface PropertyMetadataInterface extends MetadataInterface
{
    /**
     * Returns the name of the property.
     */
    public function getPropertyName(): string;

    /**
     * Extracts the value of the property from the given container.
     */
    public function getPropertyValue(mixed $containingValue): mixed;
}