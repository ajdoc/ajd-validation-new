<?php

namespace AjdVal\Context;

use SplObjectStorage;
use AjdVal\Utils\Utils;
use AjdVal\Parsers\Metadata\MetadataInterface;
use AjdVal\Parsers\Metadata\ClassMetadataInterface;
use AjdVal\Parsers\Metadata\PropertyMetadataInterface;
use AjdVal\Parsers\Metadata\MemberMetadata;

use AjdVal\Contracts\RuleInterface;

class Context implements ContextInterface
{
    /**
     * The root value of the validated object graph.
     */
    private mixed $root;

    /**
     * The currently validated value.
     */
    private mixed $value = null;

    /**
     * The currently validated object.
     */
    private ?object $object = null;

    /**
     * The property path leading to the current value.
     */
    private string $propertyPath = '';

    /**
     * Stores which objects have been validated in which group.
     *
     * @var bool[][]
     */
    private array $validatedObjects = [];

    /**
     * The current validation metadata.
     */
    private ?MetadataInterface $metadata = null;

     /**
     * The currently validated group.
     */
    private ?string $group = null;

     /**
     * The currently validated constraint.
     */
    private ?RuleInterface $rule = null;

    /**
     * @var \SplObjectStorage<object, string>
     */
    private SplObjectStorage $cachedObjectsRefs;


    public function __construct(mixed $root)
    {
        $this->root = $root;
        $this->cachedObjectsRefs = new SplObjectStorage();
    }

    public function setNode(mixed $value, ?object $object, string $propertyPath, MetadataInterface $metadata = null): void
    {
        $this->value = $value;
        $this->object = $object;
        $this->propertyPath = $propertyPath;
        $this->metadata = $metadata;
    }

    public function getRoot(): mixed
    {
        return $this->root;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getObject(): ?object
    {
        return $this->object;
    }

     public function getMetadata(): ?MetadataInterface
    {
        return $this->metadata;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): void
    {
        $this->group = $group;
    }

    public function setRule(RuleInterface $rule): void
    {
        $this->rule = $rule;
    }

    public function getRule(): ?RuleInterface
    {
        return $this->rule;
    }

    public function getClassName(): ?string
    {
        return $this->metadata instanceof MemberMetadata || $this->metadata instanceof ClassMetadataInterface ? $this->metadata->getClassName() : null;
    }

    public function getPropertyName(): ?string
    {
        return $this->metadata instanceof PropertyMetadataInterface ? $this->metadata->getPropertyName() : null;
    }

    public function getPropertyPath(string $subPath = ''): string
    {
        return Utils::appendPropertyPath($this->propertyPath, $subPath);
    }

    /**
     * @internal
     */
    public function generateCacheKey(object $object): string
    {
        if (! isset($this->cachedObjectsRefs[$object])) {
            $this->cachedObjectsRefs[$object] = spl_object_hash($object);
        }

        return $this->cachedObjectsRefs[$object];
    }
}