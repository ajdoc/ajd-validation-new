<?php

namespace AjdVal\Parsers\Metadata;
use AjdVal\Contracts\RuleInterface;
use InvalidArgumentException;
use ReflectionMethod;
use ReflectionProperty;

abstract class MemberMetadata extends MainMetadata implements PropertyMetadataInterface
{
    public $class;

    
    public $name;

    
    public $property;

    /**
     * @var \ReflectionMethod[]|\ReflectionProperty[]
     */
    private array $reflMember = [];

    /**
     * @param string $class    The name of the class this member is defined on
     * @param string $name     The name of the member
     * @param string $property The property the member belongs to
     */
    public function __construct(string $class, string $name, string $property)
    {
        $this->class = $class;
        $this->name = $name;
        $this->property = $property;
    }

    public function addRule(RuleInterface $rule): static
    {
        $this->checkRule($rule);

        parent::addRule($rule);

        return $this;
    }

    public function __sleep(): array
    {
        return array_merge(parent::__sleep(), [
            'class',
            'name',
            'property',
        ]);
    }

    /**
     * Returns the name of the member.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->class;
    }

    public function getPropertyName(): string
    {
        return $this->property;
    }

    /**
     * Returns whether this member is public.
     */
    public function isPublic(object|string $objectOrClassName): bool
    {
        return $this->getReflectionMember($objectOrClassName)->isPublic();
    }

    /**
     * Returns whether this member is protected.
     */
    public function isProtected(object|string $objectOrClassName): bool
    {
        return $this->getReflectionMember($objectOrClassName)->isProtected();
    }

    /**
     * Returns whether this member is private.
     */
    public function isPrivate(object|string $objectOrClassName): bool
    {
        return $this->getReflectionMember($objectOrClassName)->isPrivate();
    }

    /**
     * Returns the reflection instance for accessing the member's value.
     */
    public function getReflectionMember(object|string $objectOrClassName): ReflectionMethod|ReflectionProperty
    {
        $className = \is_string($objectOrClassName) ? $objectOrClassName : $objectOrClassName::class;

        if (!isset($this->reflMember[$className])) {
            $this->reflMember[$className] = $this->newReflectionMember($objectOrClassName);
        }

        return $this->reflMember[$className];
    }

    /**
     * Creates a new reflection instance for accessing the member's value.
     */
    abstract protected function newReflectionMember(object|string $objectOrClassName): ReflectionMethod|ReflectionProperty;

    private function checkRule(RuleInterface $rule): void
    {
        /*if (!\in_array(RuleInterface::PROPERTY_CONSTRAINT, (array) $rule->getTargets(), true)) {
            throw new InvalidArgumentException(sprintf('The rule "%s" cannot be put on properties or getters.', get_class($rule)));
        }

        if ($rule instanceof Composite) {
            foreach ($rule->getNestedRules() as $nestedRule) {
                $this->checkRule($nestedRule);
            }
        }*/
    }
}