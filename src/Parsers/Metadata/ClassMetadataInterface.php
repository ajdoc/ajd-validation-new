<?php

namespace AjdVal\Parsers\Metadata;

interface ClassMetadataInterface extends MetadataInterface
{
    /**
     * Returns the names of all rules properties.
     *
     * @return string[]
     */
    public function getRuledProperties(): array;

    /**
     * Check if there's any metadata attached to the given named property.
     *
     * @param string $property The property name
     */
    public function hasPropertyMetadata(string $property): bool;

    /**
     * Returns all metadata instances for the given named property.
     *
     * If your implementation does not support properties, throw an exception
     * in this method (for example a <tt>BadMethodCallException</tt>).
     *
     * @param string $property The property name
     *
     * @return PropertyMetadataInterface[]
     */
    public function getPropertyMetadata(string $property): array;

    /**
     * Returns the name of the backing PHP class.
     */
    public function getClassName(): string;
}