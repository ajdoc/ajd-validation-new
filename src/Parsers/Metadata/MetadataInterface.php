<?php

namespace AjdVal\Parsers\Metadata;

use AjdVal\Contracts\RuleInterface;

interface MetadataInterface
{
    /**
     * Returns the strategy for cascading objects.
     *
     * @see CascadingStrategy
     */
    public function getCascadingStrategy(): int;

    /**
     * Returns the strategy for traversing traversable objects.
     *
     * @see TraversalStrategy
     */
    public function getTraversalStrategy(): int;

    /**
     * Returns all constraints of this element.
     *
     * @return RuleInterface[]
     */
    public function getRules(): array;

    /**
     * Returns all constraints for a given validation group.
     *
     * @param string $group The validation group
     *
     * @return RuleInterface[]
     */
    public function findRules(string $group): array;
}