<?php 

namespace AjdVal\Parsers;

use ReflectionClass;
use ReflectionAttribute;
use AjdVal\Contracts\RuleInterface;
use AjdVal\Parsers\Metadata\ClassMetadata;
use AjDic\AjDic;

class AttributeParser extends AbstractParser
{
	public function loadMetadata(ClassMetadata $class): array 
	{
		$container = $this->getContainer();

		$reflection = $class->getReflectionClass();
		$className = $reflection->getName();
		$data = [];

		$instance = $container->make($className);
		
		foreach ($reflection->getProperties() as $property) {
			 if ($property->getDeclaringClass()->name === $className) {
			 	foreach ($this->getAttributes($property, $container) as $rule) {
			 		if ($rule instanceof RuleInterface) {
			 			$data[$property->getName()]['rules'][ \spl_object_hash($rule)] = $rule;
			 			$data[$property->getName()]['value'] = $property->getValue($instance);

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
    private function getAttributes(object $reflection, AjDic|null $container = null): iterable
    {
    	foreach ($reflection->getAttributes(RuleInterface::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
    		if (! empty($container)) {
    			yield $container->makeWith($attribute->getName(), $attribute->getArguments());
    		} else {
    			yield $attribute->newInstance();
    		}
        }
    }
}