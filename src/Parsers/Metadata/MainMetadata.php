<?php

namespace AjdVal\Parsers\Metadata;

use AjdVal\Contracts\RuleInterface;
use InvalidArgumentException;

class MainMetadata implements MetadataInterface
{
    /**
     * @var Constraint[]
     *
     */
    public $rules = [];

    /**
     * @var array
     */
    public $rulesByGroup = [];

    /**
     * The strategy for cascading objects.
     *
     * By default, objects are not cascaded.
     *
     * @var int
     * 
     */
    public $cascadingStrategy = CascadingStrategy::NONE;

    /**
     * The strategy for traversing traversable objects.
     *
     * By default, traversable objects are not traversed.
     *
     * @var int
     *
     */
    public $traversalStrategy = TraversalStrategy::NONE;

    /**
     * Is auto-mapping enabled?
     *
     * @var int
     */
    public $autoMappingStrategy = AutoMappingStrategy::NONE;

    /**
     * Returns the names of the properties that should be serialized.
     *
     * @return string[]
     */
    public function __sleep(): array
    {
        return [
            'rules',
            'rulesByGroup',
            'cascadingStrategy',
            'traversalStrategy',
            'autoMappingStrategy',
        ];
    }

    /**
     * Clones this object.
     */
    public function __clone()
    {
        $rules = $this->rules;

        $this->rules = [];
        $this->rulesByGroup = [];

        foreach ($rules as $rule) {
            $this->addRule(clone $rule);
        }
    }

    /**
     * Adds a rule.
     *
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function addRule(RuleInterface $rule): static
    {
        /*if ($rule instanceof Traverse || $rule instanceof Cascade) {
            throw new InvalidArgumentException(sprintf('The rule "%s" can only be put on classes.', get_class($rule)));
        }*/

        /*if ($constraint instanceof Valid && null === $constraint->groups) {
            $this->cascadingStrategy = CascadingStrategy::CASCADE;

            if ($constraint->traverse) {
                $this->traversalStrategy = TraversalStrategy::IMPLICIT;
            } else {
                $this->traversalStrategy = TraversalStrategy::NONE;
            }

            return $this;
        }*/

        /*if ($constraint instanceof DisableAutoMapping || $constraint instanceof EnableAutoMapping) {
            $this->autoMappingStrategy = $constraint instanceof EnableAutoMapping ? AutoMappingStrategy::ENABLED : AutoMappingStrategy::DISABLED;

            // The constraint is not added
            return $this;
        }*/

        $this->rules[] = $rule;

        if (property_exists($rule, 'groups')) {
            foreach ($rule->groups as $group) {
                $this->rulesByGroup[$group][] = $rule;
            }
        }

        return $this;
    }

    /**
     * Adds an list of rules.
     *
     * @param RuleInterface[] $rules The rules to add
     *
     * @return $this
     */
    public function addRules(array $rules): static
    {
        foreach ($rules as $rule) {
            $this->addRule($rule);
        }

        return $this;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Returns whether this element has any rules.
     */
    public function hasRules(): bool
    {
        return \count($this->rules) > 0;
    }

    /**
     * Aware of the global group (* group).
     */
    public function findRules(string $group): array
    {
        return $this->rulesByGroup[$group] ?? [];
    }

    public function getCascadingStrategy(): int
    {
        return $this->cascadingStrategy;
    }

    public function getTraversalStrategy(): int
    {
        return $this->traversalStrategy;
    }

    /**
     * @see AutoMappingStrategy
     */
    public function getAutoMappingStrategy(): int
    {
        return $this->autoMappingStrategy;
    }
}