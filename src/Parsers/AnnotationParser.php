<?php 

namespace AjdVal\Parsers;

use Doctrine\Common\Annotations\Reader;
use ReflectionClass;
use ReflectionAttribute;
use AjdVal\Contracts\RuleInterface;
use AjdVal\Parsers\Metadata\ClassMetadata;
use AjDic\AjDic;

class AnnotationParser extends AbstractParser
{
	protected $reader;

    public function __construct(Reader $reader = null)
    {
        $this->reader = $reader;
    }

	public function loadMetadata(ClassMetadata $class): array 
	{
		$container = $this->getContainer();

		$reflection = $class->getReflectionClass();
		$className = $reflection->getName();
		$data = [];

		$instance = $container->make($className);
		
		foreach ($reflection->getProperties() as $property) {
			 if ($property->getDeclaringClass()->name === $className) {
			 	foreach ($this->getAnnotations($property, $container) as $rule) {
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
    private function getAnnotations(object $reflection, AjDic|null $container = null): iterable
    {
    	if (!$this->reader) {
            return;
        }

        if ($reflection instanceof \ReflectionClass) {
            yield from $this->reader->getClassAnnotations($reflection);
        }
        if ($reflection instanceof \ReflectionMethod) {
            yield from $this->reader->getMethodAnnotations($reflection);
        }
        if ($reflection instanceof \ReflectionProperty) {
            yield from $this->reader->getPropertyAnnotations($reflection);
        }

    }
}