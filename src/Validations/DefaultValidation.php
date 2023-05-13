<?php 

namespace AjdVal\Validations;

use AjdVal\ValidatorDto;
use AjdVal\Context\ContextInterface;
use AjdVal\Context\Context;
use AjdVal\Parsers\Factory\MetadataFactoryInterface;
use AjdVal\Parsers\Metadata\MetadataInterface;
use AjdVal\Parsers\Metadata\ClassMetadataInterface;
use AjdVal\Parsers\Metadata\PropertyMetadataInterface;
use AjdVal\Parsers\Metadata\TraversalStrategy;
use AjdVal\Parsers\Metadata\CascadingStrategy;
use AjdVal\Parsers\Metadata\MainMetadata;
use AjdVal\Contracts\RuleInterface;
use AjdVal\Builder\ValidatorBuilderInterface;
use AjdVal\Utils\Utils;
use RuntimeException;
use InvalidArgumentException;
use Exception;

class DefaultValidation implements ValidationInterface
{
	private ContextInterface $context;
    private MetadataFactoryInterface $metadataFactory;
    private ValidatorDto $validatorDto;
    private ValidatorBuilderInterface $validatorBuilder;

    private string $defaultPropertyPath;
    private array $defaultGroups;
    private string $filterPath = '';

    private array $mappings = [];

	public function __construct(ContextInterface $context, MetadataFactoryInterface $metadataFactory, ValidatorDto $validatorDto)
    {
    	$this->context = $context;
        $this->defaultPropertyPath = $context->getPropertyPath();
        $this->defaultGroups = ['GROUP'];
        $this->metadataFactory = $metadataFactory;
        $this->validatorDto = $validatorDto;
        $this->validatorBuilder = $this->validatorDto->getValidatorBuilder();
    }

    public function filterPath(string $filterPath): static
    {
        $this->filterPath = $filterPath;

        return $this;
    }

    public function atPath(string $path): static
    {
        $this->defaultPropertyPath = $this->context->getPropertyPath($path);

        return $this;
    }

    public function validate(mixed $value, RuleInterface|array $rules = null, string|array $groups = null): bool 
    {
    	if (empty($rules)) {
    		$rules = null;
    	}

    	$this->mainValidate($value, $rules, $groups);

    	$mappings = $this->getMappings();

    	if (empty($mappings)) {
    		return false;
    	}

    	if ('' !== $this->filterPath) {
    		$mappings[$this->filterPath] = $mappings[$this->filterPath];
    	}

        $validator = $this->context->getRoot()['root'];
        $invalidFields = [];

    	foreach ($mappings as $field => $mapping) {            
    		foreach ($mapping['rules'] as $rule) {
                $realField = $mapping['context']->getPropertyPath() ?: $field;
                if (is_numeric($field)) {
                    $realField = $field;
                }
                
                $rule->setValidatorDto($this->validatorDto);
    			$result = $rule->validate($mapping['value'], $realField);

    			if (! $result) {
                    $invalidFields[$field] = true;
					$rule->setName($realField);

                    if (! empty($rule->getAdhocErrors())) {
                        $message = $rule->formatAdhocError();
                    } else {
                        $message = $rule->getRuleExceptionMessage();
                    }

                    $validator->buildError()
                        ->atPath($realField)
                        ->setMessage($message)
                        ->setRoot($mapping['context']->getRoot()['value'])
                        ->setInvalidValue($mapping['value'])
                        ->setRule($rule)
                        ->addViolation();
				}
    		}
    	}
        
    	return !in_array(true, array_values($invalidFields));
    }

    protected function mainValidate(mixed $value, RuleInterface|array $rules = null, string|array $groups = null): static
    {
        $groups = $groups ? $this->normalizeGroups($groups) : $this->defaultGroups;

        $previousValue = $this->context->getValue();
        $previousObject = $this->context->getObject();
        $previousMetadata = $this->context->getMetadata();
        $previousPath = $this->context->getPropertyPath();
        $previousGroup = $this->context->getGroup();
        $previousRule = null;

        if ($this->context instanceof Context || method_exists($this->context, 'getRule')) {
            $previousRule = $this->context->getRule();
        }

        // If explicit constraints are passed, validate the value against
        // those constraints
        if (null !== $rules) {
            // You can pass a single constraint or an array of constraints
            // Make sure to deal with an array in the rest of the code
            if (!\is_array($rules)) {
                $rules = [$rules];
            }

            $metadata = new MainMetadata();
            $metadata->addRules($rules);

            $this->context->setNode($previousValue, $previousObject, $previousPath, $previousMetadata);
            $this->context->setGroup($previousGroup);

            if (null !== $previousRule) {
                $this->context->setRule($previousRule);
            }

            if (\is_array($value)) {
            	$this->validateEachValueIn(
	                $value,
	                $previousObject,
	                \is_object($value) ? $this->generateCacheKey($value) : null,
	                $metadata,
	                $this->defaultPropertyPath,
	                $groups,
	                null,
	                TraversalStrategy::IMPLICIT,
	                $this->context
	            );
            } else {
            	$this->validateGenericNode(
	                $value,
	                $previousObject,
	                \is_object($value) ? $this->generateCacheKey($value) : null,
	                $metadata,
	                $this->defaultPropertyPath,
	                $groups,
	                null,
	                TraversalStrategy::IMPLICIT,
	                $this->context
	            );

	            $this->setMappings($value, $metadata->getRules(), $this->context);
            }

            return $this;
        }

        // If an object is passed without explicit constraints, validate that
        // object against the constraints defined for the object's class
        if (\is_object($value)) {
            $this->validateObject(
                $value,
                $this->defaultPropertyPath,
                $groups,
                TraversalStrategy::IMPLICIT,
                $this->context
            );

            $this->context->setNode($previousValue, $previousObject, $previousPath, $previousMetadata);
            $this->context->setGroup($previousGroup);

            return $this;
        }

        // If an array is passed without explicit constraints, validate each
        // object in the array
        if (\is_array($value)) {
            $this->validateEachObjectIn(
                $value,
                $this->defaultPropertyPath,
                $groups,
                $this->context
            );

            $this->context->setNode($previousValue, $previousObject, $previousPath, $previousMetadata);
            $this->context->setGroup($previousGroup);

            return $this;
        }

        throw new RuntimeException(sprintf('Cannot validate values of "%s" automatically. Please provide a Rule.', 'value'));
    }

    protected function normalizeGroups(string|array $groups): array
    {
        if (\is_array($groups)) {
            return $groups;
        }

        return [$groups];
    }

     private function validateGenericNode(mixed $value, ?object $object, ?string $cacheKey, ?MetadataInterface $metadata, string $propertyPath, array $groups, ?array $cascadedGroups, int $traversalStrategy, ContextInterface $context): void
    {
        $context->setNode($value, $object, $propertyPath, $metadata);

       /* foreach ($groups as $key => $group) {
            if ($group instanceof GroupSequence) {
                $this->stepThroughGroupSequence(
                    $value,
                    $object,
                    $cacheKey,
                    $metadata,
                    $propertyPath,
                    $traversalStrategy,
                    $group,
                    null,
                    $context
                );

                // Skip the group sequence when cascading, as the cascading
                // logic is already done in stepThroughGroupSequence()
                unset($groups[$key]);

                continue;
            }

            $this->validateInGroup($value, $cacheKey, $metadata, $group, $context);
        }*/

        if (0 === \count($groups)) {
            return;
        }

        if (null === $value) {
            return;
        }

        $cascadingStrategy = $metadata->getCascadingStrategy();

        if (!($cascadingStrategy & CascadingStrategy::CASCADE)) {
            return;
        }

        if ($traversalStrategy & TraversalStrategy::IMPLICIT) {
            $traversalStrategy = $metadata->getTraversalStrategy();
        }

        $cascadedGroups = null !== $cascadedGroups && \count($cascadedGroups) > 0 ? $cascadedGroups : $groups;

        if (\is_array($value)) {
            // Arrays are always traversed, independent of the specified
            // traversal strategy
            $this->validateEachObjectIn(
                $value,
                $propertyPath,
                $cascadedGroups,
                $context
            );

            return;
        }

        if (!\is_object($value)) {
            throw new InvalidArgumentException(sprintf('Cannot create metadata for non-objects. Got: "%s".', \gettype($value)));
        }

        $this->validateObject(
            $value,
            $propertyPath,
            $cascadedGroups,
            $traversalStrategy,
            $context
        );

    }

     private function validateClassNode(object $object, ?string $cacheKey, ClassMetadataInterface $metadata, string $propertyPath, array $groups, ?array $cascadedGroups, int $traversalStrategy, ContextInterface $context): void
    {
        $context->setNode($object, $object, $propertyPath, $metadata);

        /*if (!$context->isObjectInitialized($cacheKey)) {
            foreach ($this->objectInitializers as $initializer) {
                $initializer->initialize($object);
            }

            $context->markObjectAsInitialized($cacheKey);
        }*/

        /*foreach ($groups as $key => $group) {
            
            $defaultOverridden = false;

            // Use the object hash for group sequences
            $groupHash = \is_object($group) ? $this->generateCacheKey($group, true) : $group;

            if ($context->isGroupValidated($cacheKey, $groupHash)) {
                
                unset($groups[$key]);

                continue;
            }

            $context->markGroupAsValidated($cacheKey, $groupHash);

            
            if (Constraint::DEFAULT_GROUP === $group) {
                if ($metadata->hasGroupSequence()) {
                    
                    $group = $metadata->getGroupSequence();
                    $defaultOverridden = true;
                } elseif ($metadata->isGroupSequenceProvider()) {
                    
                    $group = $object->getGroupSequence();
                    $defaultOverridden = true;

                    if (!$group instanceof GroupSequence) {
                        $group = new GroupSequence($group);
                    }
                }
            }

            // If the groups (=[<G1,G2>,G3,G4]) contain a group sequence
            // (=<G1,G2>), then call validateClassNode() with each entry of the
            // group sequence and abort if necessary (G1, G2)
            if ($group instanceof GroupSequence) {
                $this->stepThroughGroupSequence(
                    $object,
                    $object,
                    $cacheKey,
                    $metadata,
                    $propertyPath,
                    $traversalStrategy,
                    $group,
                    $defaultOverridden ? Constraint::DEFAULT_GROUP : null,
                    $context
                );

                // Skip the group sequence when validating properties, because
                // stepThroughGroupSequence() already validates the properties
                unset($groups[$key]);

                continue;
            }

            $this->validateInGroup($object, $cacheKey, $metadata, $group, $context);
        }*/

        // If no more groups should be validated for the property nodes,
        // we can safely quit
        if (0 === \count($groups)) {
            return;
        }
        
        foreach ($metadata->getRuledProperties() as $propertyName) {
            
            foreach ($metadata->getPropertyMetadata($propertyName) as $propertyMetadata) {
                if (!$propertyMetadata instanceof PropertyMetadataInterface) {
                    throw new InvalidArgumentException(sprintf('The property metadata instances should implement "AjdVal\Parsers\Metadata\PropertyMetadataInterface", got: "%s".', get_class($propertyMetadata)));
                }

               /* if ($propertyMetadata instanceof GetterMetadata) {
                    $propertyValue = new LazyProperty(static fn () => $propertyMetadata->getPropertyValue($object));
                } else {*/
                    $propertyValue = $propertyMetadata->getPropertyValue($object);
                // }

                $pathAppend = Utils::appendPropertyPath($propertyPath, $propertyName);

                if ('' !== $this->filterPath && $pathAppend != $this->filterPath) {
                	continue;
                }

                $this->validateGenericNode(
                    $propertyValue,
                    $object,
                    $cacheKey.':'.$object::class.':'.$propertyName,
                    $propertyMetadata,
                    $pathAppend,
                    $groups,
                    $cascadedGroups,
                    TraversalStrategy::IMPLICIT,
                    $context
                );
                $this->setMappings($propertyValue, $propertyMetadata->getRules(), $context, $pathAppend);
            }
        }

        if ($traversalStrategy & TraversalStrategy::IMPLICIT) {
            $traversalStrategy = $metadata->getTraversalStrategy();
        }

        
        if (!($traversalStrategy & (TraversalStrategy::IMPLICIT | TraversalStrategy::TRAVERSE))) {
            return;
        }

        if ($traversalStrategy & TraversalStrategy::IMPLICIT && !$object instanceof \Traversable) {
            return;
        }

        
        if (!$object instanceof \Traversable) {
            throw new Exception(sprintf('Traversal was enabled for "%s", but this class does not implement "\Traversable".', get_class($object)));
        }

        $this->validateEachObjectIn(
            $object,
            $propertyPath,
            $groups,
            $context
        );
    }


    private function validateObject(object $object, string $propertyPath, array $groups, int $traversalStrategy, ContextInterface $context): void
    {
        try {
            $classMetadata = $this->metadataFactory->getMetadataFor($object);

            if (! $classMetadata instanceof ClassMetadataInterface) {
                throw new InvalidArgumentException(sprintf('The metadata factory should return instances of "\AjdVal\Parsers\Metadata\ClassMetadataInterface", got: "%s".', get_class($classMetadata)));
            }

            $this->validateClassNode(
                $object,
                $this->generateCacheKey($object),
                $classMetadata,
                $propertyPath,
                $groups,
                null,
                $traversalStrategy,
                $context
            );

        } catch (\Throwable $e) {
            // Rethrow if not Traversable
            if (!$object instanceof \Traversable) {
                throw $e;
            }

            // Rethrow unless IMPLICIT or TRAVERSE
            if (!($traversalStrategy & (TraversalStrategy::IMPLICIT | TraversalStrategy::TRAVERSE))) {
                throw $e;
            }

            $this->validateEachObjectIn(
                $object,
                $propertyPath,
                $groups,
                $context
            );
        }
    }

    private function validateEachObjectIn(iterable $collection, string $propertyPath, array $groups, ContextInterface $context): void
    {
        foreach ($collection as $key => $value) {
        	$path = $propertyPath.'['.$key.']';
            if (\is_array($value)) {
                // Also traverse nested arrays
                $this->validateEachObjectIn(
                    $value,
                    $path,
                    $groups,
                    $context
                );

                continue;
            }

            // Scalar and null values in the collection are ignored
            if (\is_object($value)) {
                $this->validateObject(
                    $value,
                    $path,
                    $groups,
                    TraversalStrategy::IMPLICIT,
                    $context
                );
            }
        }
    }

    private function validateEachValueIn(iterable $collection, ?object $object, ?string $cacheKey, ?MetadataInterface $metadata, string $propertyPath, array $groups, ?array $cascadedGroups, int $traversalStrategy, ContextInterface $context): void
    {
        foreach ($collection as $key => $value) {
        	$path = $propertyPath.'['.$key.']';
        	$pathForMapping = ! empty($propertyPath) ? $propertyPath.'.'.$key : $key;
        	
        	if ('' !== $this->filterPath && $pathForMapping != $this->filterPath) {
            	continue;
            }

            if (\is_array($value)) {
                // Also traverse nested arrays
                $this->validateEachValueIn(
                    $value,
                    $object,
                    $cacheKey,
                    $metadata,
                    $path,
                    $groups, 
                    $cascadedGroups,
                    $traversalStrategy,
                    $context
                );

                $this->setMappings($value, $metadata->getRules(), $context, $pathForMapping);

                continue;
            }

            // Scalar and null values in the collection are ignored
            if (\is_object($value)) {
                $this->validateObject(
                    $value,
                    $path,
                    $groups,
                    TraversalStrategy::IMPLICIT,
                    $context
                );
            } else {
            	$this->validateGenericNode(
	                $value,
	                $object,
	                $cacheKey,
	                $metadata,
	                $path,
	                $groups,
	                $cascadedGroups,
	                $traversalStrategy,
	                $context
	            );

	            $this->setMappings($value, $metadata->getRules(), $context, $pathForMapping);
            }
        }
    }

    private function generateCacheKey(object $object, bool $dependsOnPropertyPath = false): string
    {
        if ($this->context instanceof Context) {
            $cacheKey = $this->context->generateCacheKey($object);
        } else {
            $cacheKey = spl_object_hash($object);
        }

        if ($dependsOnPropertyPath) {
            $cacheKey .= $this->context->getPropertyPath();
        }

        return $cacheKey;
    }

    private function setMappings(mixed $value, array $rules, ContextInterface $context, $path = null): void
    {
    	$this->mappings[$path ?? 'value'] = [
    		'rules' => $rules,
    		'context' => $context,
    		'value' => $value
    	];
    }

    public function getMappings(): array 
    {
    	return $this->mappings;
    }
}