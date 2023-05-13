<?php

namespace AjdVal\Parsers\Metadata;

use AjdVal\Contracts\RuleInterface;
use ReflectionClass;
use InvalidArgumentException;

class ClassMetadata extends MainMetadata implements ClassMetadataInterface
{
    /**
     * @var string
     *
     */
    public $name;

    /**
     * @var string
     *
     */
    public $defaultGroup;

    /**
     * @var MemberMetadata[][]
     *
     */
    public $members = [];

    /**
     * @var PropertyMetadata[]
     *
     */
    public $properties = [];

    /**
     * @var GetterMetadata[]
     *
     */
    public $getters = [];

    /**
     * @var GroupSequence
     *
     */
    public $groupSequence;

    /**
     * @var bool
     *
     */
    public $groupSequenceProvider = false;

    /**
     * The strategy for traversing traversable objects.
     *
     *
     * @var int
     *
     */
    public $traversalStrategy = TraversalStrategy::IMPLICIT;

    private ReflectionClass $reflClass;

    public function __construct(string $class)
    {
        $this->name = $class;
        // class name without namespace
        if (false !== $nsSep = strrpos($class, '\\')) {
            $this->defaultGroup = substr($class, $nsSep + 1);
        } else {
            $this->defaultGroup = $class;
        }
    }

    public function __sleep(): array
    {
        $parentProperties = parent::__sleep();

        // Don't store the cascading strategy. Classes never cascade.
        unset($parentProperties[array_search('cascadingStrategy', $parentProperties)]);

        return array_merge($parentProperties, [
            'getters',
            'groupSequence',
            'groupSequenceProvider',
            'members',
            'name',
            'properties',
            'defaultGroup',
        ]);
    }

    public function getClassName(): string
    {
        return $this->name;
    }

    /**
     * Returns the name of the default group for this class.
     */
    public function getDefaultGroup(): string
    {
        return $this->defaultGroup;
    }

    /**
     * Add Rule
     */
    public function addRule(RuleInterface $rule): static
    {
        $this->checkRule($rule);

        /*if ($rule instanceof Traverse) {
            if ($rule->traverse) {
                // If traverse is true, traversal should be explicitly enabled
                $this->traversalStrategy = TraversalStrategy::TRAVERSE;
            } else {
                // If traverse is false, traversal should be explicitly disabled
                $this->traversalStrategy = TraversalStrategy::NONE;
            }

            // The constraint is not added
            return $this;
        }*/

        /*if ($rule instanceof Cascade) {
            $this->cascadingStrategy = CascadingStrategy::CASCADE;

            foreach ($this->getReflectionClass()->getProperties() as $property) {
                if (isset($rule->exclude[$property->getName()])) {
                    continue;
                }

                if ($property->hasType() && (('array' === $type = $property->getType()->getName()) || class_exists($type))) {
                    $this->addPropertyConstraint($property->getName(), new Valid());
                }
            }

            // The constraint is not added
            return $this;
        }*/

        $constraint->addImplicitGroupName($this->getDefaultGroup());

        parent::addRule($rule);

        return $this;
    }

    /**
     * Adds a rule to the given property.
     *
     * @return $this
     */
    public function addPropertyRule(string $property, RuleInterface $rule): static
    {
        if (!isset($this->properties[$property])) {
            
            $this->properties[$property] = new PropertyMetadata($this->getClassName(), $property);

            $this->addPropertyMetadata($this->properties[$property]);
        }

        // $rule->addImplicitGroupName($this->getDefaultGroup());

        $this->properties[$property]->addRule($rule);

        return $this;
    }

    /**
     * @param RuleInterface[] $constraints
     *
     * @return $this
     */
    public function addPropertyRules(string $property, array $rules): static
    {
        foreach ($rules as $rule) {
            $this->addPropertyRule($property, $rule);
        }

        return $this;
    }

    /**
     * Adds a rule to the getter of the given property.
     *
     *
     * @return $this
     */
    /*public function addGetterRule(string $property, RuleInterface $rule): static
    {
        if (!isset($this->getters[$property])) {
            $this->getters[$property] = new GetterMetadata($this->getClassName(), $property);

            $this->addPropertyMetadata($this->getters[$property]);
        }

        $constraint->addImplicitGroupName($this->getDefaultGroup());

        $this->getters[$property]->addRule($rule);

        return $this;
    }*/

    /**
     * Adds a constraint to the getter of the given property.
     *
     * @return $this
     */
    /*public function addGetterMethodRule(string $property, string $method, RuleInterface $rule): static
    {
        if (!isset($this->getters[$property])) {
            $this->getters[$property] = new GetterMetadata($this->getClassName(), $property, $method);

            $this->addPropertyMetadata($this->getters[$property]);
        }

        $constraint->addImplicitGroupName($this->getDefaultGroup());

        $this->getters[$property]->addRule($rule);

        return $this;
    }*/

    /**
     * @param RuleInterface[] $rules
     *
     * @return $this
     */
    /*public function addGetterRules(string $property, array $rules): static
    {
        foreach ($rules as $rule) {
            $this->addGetterRule($property, $rule);
        }

        return $this;
    }*/

    /**
     * @param RuleInterface[] $rules
     *
     * @return $this
     */
    /*public function addGetterMethodRules(string $property, string $method, array $constraints): static
    {
        foreach ($constraints as $constraint) {
            $this->addGetterMethodRule($property, $method, $constraint);
        }

        return $this;
    }*/

    /**
     * Merges the constraints of the given metadata into this object.
     *
     * @return void
     */
    public function mergeRules(self $source)
    {
        /*if ($source->isGroupSequenceProvider()) {
            $this->setGroupSequenceProvider(true);
        }*/

        foreach ($source->getRules() as $rule) {
            $this->addRule(clone $rule);
        }

        foreach ($source->getRuleddProperties() as $property) {
            foreach ($source->getPropertyMetadata($property) as $member) {
                $member = clone $member;

                foreach ($member->getRules() as $rule) {
                    if (\in_array($rule::DEFAULT_GROUP, $rule->groups, true)) {
                        $member->rulesByGroup[$this->getDefaultGroup()][] = $constraint;
                    }

                    $constraint->addImplicitGroupName($this->getDefaultGroup());
                }

                $this->addPropertyMetadata($member);

                if ($member instanceof MemberMetadata && !$member->isPrivate($this->name)) {
                    $property = $member->getPropertyName();

                    if ($member instanceof PropertyMetadata && !isset($this->properties[$property])) {
                        $this->properties[$property] = $member;
                    } /*elseif ($member instanceof GetterMetadata && !isset($this->getters[$property])) {
                        $this->getters[$property] = $member;
                    }*/
                }
            }
        }
    }

    public function hasPropertyMetadata(string $property): bool
    {
        return \array_key_exists($property, $this->members);
    }

    public function getPropertyMetadata(string $property): array
    {
        return $this->members[$property] ?? [];
    }

    public function getRuledProperties(): array
    {
        return array_keys($this->members);
    }

    /**
     * Sets the default group sequence for this class.
     *
     * @param string[]|GroupSequence $groupSequence An array of group names
     *
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    /*public function setGroupSequence(array|GroupSequence $groupSequence): static
    {
        if ($this->isGroupSequenceProvider()) {
            throw new GroupDefinitionException('Defining a static group sequence is not allowed with a group sequence provider.');
        }

        if (\is_array($groupSequence)) {
            $groupSequence = new GroupSequence($groupSequence);
        }

        if (\in_array(Constraint::DEFAULT_GROUP, $groupSequence->groups, true)) {
            throw new GroupDefinitionException(sprintf('The group "%s" is not allowed in group sequences.', Constraint::DEFAULT_GROUP));
        }

        if (!\in_array($this->getDefaultGroup(), $groupSequence->groups, true)) {
            throw new GroupDefinitionException(sprintf('The group "%s" is missing in the group sequence.', $this->getDefaultGroup()));
        }

        $this->groupSequence = $groupSequence;

        return $this;
    }*/

    /*public function hasGroupSequence(): bool
    {
        return isset($this->groupSequence) && \count($this->groupSequence->groups) > 0;
    }

    public function getGroupSequence(): ?GroupSequence
    {
        return $this->groupSequence;
    }*/

    /**
     * Returns a ReflectionClass instance for this class.
     */
    public function getReflectionClass(): ReflectionClass
    {
        return $this->reflClass ??= new ReflectionClass($this->getClassName());
    }

    /**
     * Sets whether a group sequence provider should be used.
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    /*public function setGroupSequenceProvider(bool $active)
    {
        if ($this->hasGroupSequence()) {
            throw new GroupDefinitionException('Defining a group sequence provider is not allowed with a static group sequence.');
        }

        if (!$this->getReflectionClass()->implementsInterface(GroupSequenceProviderInterface::class)) {
            throw new GroupDefinitionException(sprintf('Class "%s" must implement GroupSequenceProviderInterface.', $this->name));
        }

        $this->groupSequenceProvider = $active;
    }

    public function isGroupSequenceProvider(): bool
    {
        return $this->groupSequenceProvider;
    }*/

    public function getCascadingStrategy(): int
    {
        return $this->cascadingStrategy;
    }

    private function addPropertyMetadata(PropertyMetadataInterface $metadata): void
    {
        $property = $metadata->getPropertyName();

        $this->members[$property][] = $metadata;
    }

    private function checkRule(RuleInterface $rule): void
    {
        /*if (!\in_array(RuleInterface::CLASS_CONSTRAINT, (array) $rule->getTargets(), true)) {
            throw new InvalidArgumentException(sprintf('The rule "%s" cannot be put on classes.', get_class($rule)));
        }

        if ($rule instanceof Composite) {
            foreach ($rule->getNestedRules() as $nestedRule) {
                $this->checkRule($nestedRule);
            }
        }*/
    }
}