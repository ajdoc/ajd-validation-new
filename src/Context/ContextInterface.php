<?php

namespace AjdVal\Context;

use AjdVal\Parsers\Metadata\MetadataInterface;
use AjdVal\Contracts\RuleInterface;

interface ContextInterface
{
     
    public function getObject(): ?object;

    public function setNode(mixed $value, ?object $object, string $propertyPath, MetadataInterface $metadata = null): void;

    public function getRoot(): mixed;

    public function getValue(): mixed;

    public function getClassName(): ?string;

    public function getPropertyName(): ?string;

    public function getPropertyPath(string $subPath = ''): string;

    public function getMetadata(): ?MetadataInterface;

    public function getGroup(): ?string;

    public function setGroup(?string $group): void;

    public function setRule(RuleInterface $rule): void;

    public function getRule(): ?RuleInterface;
}