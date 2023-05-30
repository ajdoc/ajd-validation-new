<?php 

namespace AjdVal\Parsers;

use ReflectionClass;
use ReflectionAttribute;
use AjdVal\Contracts\RuleInterface;
use AjdVal\Parsers\Metadata\ClassMetadata;
use AjdVal\Validators\ValidatorsInterface;
use AjDic\AjDic;

class AttributeParser extends AbstractParser
{
	public function loadMetadata(ClassMetadata $class): array 
	{
		$container = $this->getContainer();
		$validator = $this->getValidator();

		$reflection = $class->getReflectionClass();
		$className = $reflection->getName();
		$data = [];

		$instance = $container->make($className);
		
		foreach ($reflection->getProperties() as $property) {
			 if ($property->getDeclaringClass()->name === $className) {
			 	foreach ($this->getAttributes($property, $container, $validator) as $rule) {
			 		if ($rule instanceof RuleInterface) {
			 			$class->addPropertyRule($property->name, $rule);
			 		}
			 	}
			 }
		}

		return $data;
	}

	/**
     * @param \ReflectionClass|\ReflectionMethod|\ReflectionProperty $reflection
     */
    private function getAttributes(object $reflection, AjDic|null $container = null, ValidatorsInterface|null $validator = null): iterable
    {
    	foreach ($reflection->getAttributes(RuleInterface::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
    		$arguments = $attribute->getArguments();
    		$handlers = $this->resolveHandler($attribute->getName(), $container, $validator, $arguments);

    		if (! empty($container)) {
    			$arguments = $validator?->processHandlerPreInit($arguments, $attribute->getName(), $handlers);
    			$instance = $container->makeWith($attribute->getName(), $arguments);
    		} else {
    			$instance = $attribute->newInstance();
    		}

    		yield $this->resolveToExpr($instance, $attribute->getName(), $arguments, $container, $validator, $handlers);
        }
    }
}