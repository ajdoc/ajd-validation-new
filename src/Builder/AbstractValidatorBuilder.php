<?php 

namespace AjdVal\Builder;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use AjdVal\Factory\FactoryInterface;
use AjdVal\Factory\FactoryTypeEnum;
use AjdVal\Parsers\ParserInterface;
use AjdVal\Parsers\AnnotationParser;
use AjdVal\Validators\DefaultValidator;
use AjdVal\Validators\ValidatorsInterface;
use ReflectionClass;
use InvalidArgumentException;
use LogicException;

abstract class AbstractValidatorBuilder implements ValidatorBuilderInterface
{
	protected FactoryInterface|null $rulesFactory = null;
	protected FactoryInterface|null $rulesHandlerFactory = null;
	protected FactoryInterface|null $rulesExceptionFactory = null;
	protected FactoryInterface|null $filtersFactory = null;
	protected FactoryInterface|null $logicsFactory = null;
    protected Reader $annotationReader;

    protected array $parsers = [];

    protected string $qualifiedValidatorClass;

	abstract public function initialize(): ValidatorBuilderInterface;

	protected function initializeValidateFactoryType(FactoryInterface $factory, FactoryTypeEnum $factoryType)
	{
		$factory->initialize();
		if ($factory->getFactoryType() !== $factoryType->value) {
			throw new InvalidArgumentException('Invalid Factory '.$factoryType->value.'.');
		}
	}

    public function setRulesFactory(FactoryInterface $rulesFactory): ValidatorBuilderInterface
    {
    	$this->initializeValidateFactoryType($rulesFactory, FactoryTypeEnum::TYPE_RULES);
    	$this->rulesFactory = $rulesFactory;

    	return $this;
    }

    public function setFiltersFactory(FactoryInterface $filtersFactory): ValidatorBuilderInterface
    {
    	$this->initializeValidateFactoryType($filtersFactory, FactoryTypeEnum::TYPE_FILTERS);
    	$this->filtersFactory = $filtersFactory;

    	return $this;
    }

    public function setRulesHandlerFactory(FactoryInterface $rulesHandlerFactory): ValidatorBuilderInterface
    {
    	$this->initializeValidateFactoryType($rulesHandlerFactory, FactoryTypeEnum::TYPE_RULE_HANDLERS);
    	$this->rulesHandlerFactory = $rulesHandlerFactory;

    	return $this;
    }

    public function setRulesExceptionFactory(FactoryInterface $rulesExceptionFactory): ValidatorBuilderInterface
    {
    	$this->initializeValidateFactoryType($rulesExceptionFactory, FactoryTypeEnum::TYPE_RULE_EXCEPTIONS);
    	$this->rulesExceptionFactory = $rulesExceptionFactory;

    	return $this;
    }

    public function setLogicsFactory(FactoryInterface $logicsFactory): ValidatorBuilderInterface
    {
    	$this->initializeValidateFactoryType($logicsFactory, FactoryTypeEnum::TYPE_LOGICS);
    	$this->logicsFactory = $logicsFactory;

    	return $this;
    }

    public function getRulesFactory(): FactoryInterface|null
    {
    	return $this->rulesFactory;
    }

    public function getFiltersFactory(): FactoryInterface|null
    {
    	return $this->filtersFactory;
    }

    public function getRulesHandlerFactory(): FactoryInterface|null
    {
    	return $this->rulesHandlerFactory;
    }

    public function getRulesExceptionFactory(): FactoryInterface|null
    {
    	return $this->rulesExceptionFactory;
    }
    
    public function getLogicsFactory(): FactoryInterface|null
    {
    	return $this->logicsFactory;
    }

    public function addParser(ParserInterface $parser): ValidatorBuilderInterface
    {
        $this->parsers[] = $parser;

        return $this;
    }

    public function addParsers(array $parsers): ValidatorBuilderInterface
    {
        $this->parsers = array_merge($this->parsers, $parsers);

        return $this;
    }

    public function getParsers(): array
    {
        $parsers = [];

        return array_merge($parsers, $this->parsers);
    }

    public function setValidatorClass(string|null $validator = null): ValidatorBuilderInterface
    {
        if (empty($validator)) {
            $this->qualifiedValidatorClass = DefaultValidator::class;

            return $this;
        }

        if (! $this->checkValidatorClass($validator)) {
            return $this;
        }

        $this->qualifiedValidatorClass = $validator;
        
        return $this;
    }

    public function getValidatorClass(): string
    {
        return $this->qualifiedValidatorClass;
    }

    protected function checkValidatorClass(string $validator): bool
    {
        $reflection = new ReflectionClass($validator);

        $interfaces  = array_keys($reflection->getInterfaces());

        if (!in_array(ValidatorsInterface::class, $interfaces, true)) {
            return false;
        }

        return true;
    }

    public function setDoctrineAnnotationReader(?Reader $reader): ValidatorBuilderInterface
    {
        $this->annotationReader = $reader;

        $this->parsers[] = new AnnotationParser($this->annotationReader);

        return $this;
    }

    public function addDefaultDoctrineAnnotationReader(): ValidatorBuilderInterface
    {
        $this->annotationReader = $this->createAnnotationReader();

        $this->parsers[] = new AnnotationParser($this->annotationReader);

        return $this;
    }

    public function getAnnotationReader(): Reader
    {
        return $this->annotationReader;
    }

    protected function createAnnotationReader(): Reader
    {
        if (! class_exists(AnnotationReader::class)) {
            throw new LogicException('Enabling annotation based constraint mapping requires the packages doctrine/annotations.');
        }

        return new AnnotationReader();

    }
}