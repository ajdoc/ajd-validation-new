<?php

namespace AjdVal\Parsers\Factory;


use AjdVal\Parsers\Metadata\ClassMetadata;
use AjdVal\Parsers\ParserInterface;
use AjdVal\Parsers\Metadata\MetadataInterface;
use InvalidArgumentException;

class MetadataFactory implements MetadataFactoryInterface
{
    protected $loader;

    /**
     * The loaded metadata, indexed by class name.
     *
     * @var ClassMetadata[]
     */
    protected $loadedClasses = [];

    public function __construct(ParserInterface $loader = null)
    {
        $this->loader = $loader;
    }

    public function getMetadataFor(mixed $value): MetadataInterface
    {
        if (! \is_object($value) && ! \is_string($value)) {
            throw new InvalidArgumentException(sprintf('Cannot create metadata for non-objects. Got: "%s".', get_class($value)));
        }

        $class = ltrim(\is_object($value) ? $value::class : $value, '\\');

        if (isset($this->loadedClasses[$class])) {
            return $this->loadedClasses[$class];
        }

        if (! class_exists($class) && ! interface_exists($class, false)) {
            throw new InvalidArgumentException(sprintf('The class or interface "%s" does not exist.', $class));
        }

        $metadata = new ClassMetadata($class);

        $this->loader?->loadMetadata($metadata);
        
        // Include constraints from the parent class
        $this->mergeConstraints($metadata);

        return $this->loadedClasses[$class] = $metadata;
    }

    private function mergeConstraints(ClassMetadata $metadata): void
    {
        if ($metadata->getReflectionClass()->isInterface()) {
            return;
        }

        // Include constraints from the parent class
        if ($parent = $metadata->getReflectionClass()->getParentClass()) {
            $metadata->mergeConstraints($this->getMetadataFor($parent->name));
        }

        // Include constraints from all directly implemented interfaces
        foreach ($metadata->getReflectionClass()->getInterfaces() as $interface) {
            /*if ('Symfony\Component\Validator\GroupSequenceProviderInterface' === $interface->name) {
                continue;
            }*/

            if ($parent && \in_array($interface->getName(), $parent->getInterfaceNames(), true)) {
                continue;
            }

            $metadata->mergeConstraints($this->getMetadataFor($interface->name));
        }
    }

    public function hasMetadataFor(mixed $value): bool
    {
        if (! \is_object($value) && ! \is_string($value)) {
            return false;
        }

        $class = ltrim(\is_object($value) ? $value::class : $value, '\\');

        return class_exists($class) || interface_exists($class, false);
    }

    /**
     * Replaces backslashes by dots in a class name.
     */
    private function escapeClassName(string $class): string
    {
        if (str_contains($class, '@')) {
            // anonymous class: replace all PSR6-reserved characters
            return str_replace(["\0", '\\', '/', '@', ':', '{', '}', '(', ')'], '.', $class);
        }

        return str_replace('\\', '.', $class);
    }
}