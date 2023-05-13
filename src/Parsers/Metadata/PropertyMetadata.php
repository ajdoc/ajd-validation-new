<?php

namespace AjdVal\Parsers\Metadata;

use LogicException;
use ReflectionMethod;
use ReflectionProperty;

class PropertyMetadata extends MemberMetadata
{
    /**
     * @param string $class The class this property is defined on
     * @param string $name  The name of this property
     *
     * @throws LogicException
     */
    public function __construct(string $class, string $name)
    {
        if (!property_exists($class, $name)) {
            throw new LogicException(sprintf('Property "%s" does not exist in class "%s".', $name, $class));
        }
        
        parent::__construct($class, $name, $name);
    }

    public function getPropertyValue(mixed $object): mixed
    {
        $reflProperty = $this->getReflectionMember($object);

        if ($reflProperty->hasType() && !$reflProperty->isInitialized($object)) {
            // There is no way to check if a property has been unset or if it is uninitialized.
            // When trying to access an uninitialized property, __get method is triggered.

            // If __get method is not present, no fallback is possible
            // Otherwise we need to catch an Error in case we are trying to access an uninitialized but set property.
            if (!method_exists($object, '__get')) {
                return null;
            }

            try {
                return $reflProperty->getValue($object);
            } catch (\Error) {
                return null;
            }
        }

        return $reflProperty->getValue($object);
    }

    protected function newReflectionMember(object|string $objectOrClassName): ReflectionMethod|ReflectionProperty
    {
        $originalClass = \is_string($objectOrClassName) ? $objectOrClassName : $objectOrClassName::class;

        while (!property_exists($objectOrClassName, $this->getName())) {
            $objectOrClassName = get_parent_class($objectOrClassName);

            if (false === $objectOrClassName) {
                throw new LogicException(sprintf('Property "%s" does not exist in class "%s".', $this->getName(), $originalClass));
            }
        }

        return new ReflectionProperty($objectOrClassName, $this->getName());
    }
}