<?php

namespace AjdVal\Parsers\Factory;

use AjdVal\Parsers\Metadata\MetadataInterface;
use InvalidArgumentException;

interface MetadataFactoryInterface
{
    /**
     * Returns the metadata for the given value.
     *
     * @throws InvalidArgumentException If no metadata exists for the given value
     */
    public function getMetadataFor(mixed $value): MetadataInterface;

    /**
     * Returns whether the class is able to return metadata for the given value.
     */
    public function hasMetadataFor(mixed $value): bool;
}