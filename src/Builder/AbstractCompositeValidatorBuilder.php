<?php 

namespace AjdVal\Builder;

use Doctrine\Common\Annotations\Reader;
use AjdVal\Factory\AbstractFactory;
use AjdVal\Factory\FactoryTypeEnum;
use AjdVal\Factory\FactoryInterface;
use AjdVal\Parsers\ParserInterface;

abstract class AbstractCompositeValidatorBuilder extends AbstractValidatorBuilder
{
	protected $validatorBuilders;

	public function __construct(ValidatorBuilderInterface ...$validatorBuilders)
	{
		$this->validatorBuilders = $validatorBuilders;
	}

	public function addValidatorBuilder(ValidatorBuilderInterface $validatorBuilder): static
	{
		$this->validatorBuilders[] = $validatorBuilder;

		return $this;
	}

	public function getValidatorBuilder(): ValidatorBuilderInterface
	{
		if (empty($this->validatorBuilders)) {
			return $this;
		}

		$rulesFactories = [];
		$ruleHandlerFactories = [];
		$ruleExceptionFactories = [];
		$filterFactories = [];
		$logicFactories = [];

		$parsers = [];
		$qualifiedValidatorClass = '';
		$annotationReader = null;
		
		foreach ($this->validatorBuilders as $validatorBuilder) {
			$rulesFactories = $this->checkReturnFactories($rulesFactories, $validatorBuilder->getRulesFactory());
			$ruleHandlerFactories = $this->checkReturnFactories($ruleHandlerFactories, $validatorBuilder->getRulesHandlerFactory());
			$ruleExceptionFactories = $this->checkReturnFactories($ruleExceptionFactories, $validatorBuilder->getRulesExceptionFactory());
			$filterFactories = $this->checkReturnFactories($filterFactories, $validatorBuilder->getFiltersFactory());
			$logicFactories = $this->checkReturnFactories($logicFactories, $validatorBuilder->getLogicsFactory());

			if (! empty($validatorBuilder->getParsers())) {
				$parsers = array_merge($parsers, $validatorBuilder->getParsers());
			}

			if (! empty($validatorBuilder->getValidatorClass())) {
				$qualifiedValidatorClass = $validatorBuilder->getValidatorClass();
			}

			if (! empty($validatorBuilder->getAnnotationReader())) {
				$annotationReader = $validatorBuilder->getAnnotationReader();
			}
		}

		$newRuleFactory = $this->createNewFactory($rulesFactories, FactoryTypeEnum::TYPE_RULES);
		$newRuleHandlerFactory = $this->createNewFactory($ruleHandlerFactories, FactoryTypeEnum::TYPE_RULE_HANDLERS);
		$newRuleExceptionFactory = $this->createNewFactory($ruleExceptionFactories, FactoryTypeEnum::TYPE_RULE_EXCEPTIONS);
		$newFilterFactory = $this->createNewFactory($filterFactories, FactoryTypeEnum::TYPE_FILTERS);
		$newLogicFactory = $this->createNewFactory($logicFactories, FactoryTypeEnum::TYPE_LOGICS);

		return $this->createNewValidatorBuilder($newRuleFactory, $newRuleHandlerFactory, $newRuleExceptionFactory, $newFilterFactory, $newLogicFactory, $parsers, $qualifiedValidatorClass, $annotationReader)->initialize();
	}

	protected function checkReturnFactories(array $factories, ?FactoryInterface $factory): array
	{
		if (!empty($factory)) {
			$factories[] = $factory;	
		}

		return $factories;
	}

	protected function createNewFactory(array $factories, FactoryTypeEnum $factoryType): FactoryInterface|null
	{
		$newFactory = null;

		if (! empty($factories)) {
			$newFactory = new class($factories, $factoryType) extends AbstractFactory {
				public function __construct(protected array $factories, protected FactoryTypeEnum $passedFactoryType)
				{
					parent::__construct();
				}

				public function initialize()
				{
					$this->processFactories();
				}

				public function getPassedFactoryType()
				{
					return $this->passedFactoryType;
				}

				protected function processFactories()
				{
					$obj = $this->setFactoryType($this->passedFactoryType);

					foreach ($this->factories as $factory) {
						$obj->appendDirectories($factory->getDirectories());
						$obj->appendNamespaces($factory->getNamespaces());

						if($factory->getAllowNotFound()) {
							$obj->allowNotFound();
						}
					}
				}
			};
		}

		return $newFactory;
	}

	public function createNewValidatorBuilder(...$factories)
	{
		return new class(...$factories) extends AbstractValidatorBuilder {

			protected $factories;

			public function __construct(...$factories)
			{
				$this->factories = $factories;
			}

			public function initialize(): ValidatorBuilderInterface
			{
				$chain = $this;
				
				if (! empty($this->factories)) {

					foreach ($this->factories as $factory) {
						if (empty($factory)) {
							continue;
						}

						if ($factory instanceof FactoryInterface) {
							if ($factory->getPassedFactoryType() === FactoryTypeEnum::TYPE_RULES) {
								$chain->setRulesFactory($factory);
							}

							if ($factory->getPassedFactoryType() === FactoryTypeEnum::TYPE_RULE_HANDLERS) {
								$chain->setRulesHandlerFactory($factory);
							}

							if ($factory->getPassedFactoryType() === FactoryTypeEnum::TYPE_RULE_EXCEPTIONS) {
								$chain->setRulesExceptionFactory($factory);
							}

							if ($factory->getPassedFactoryType() === FactoryTypeEnum::TYPE_FILTERS) {
								$chain->setFiltersFactory($factory);
							}

							if ($factory->getPassedFactoryType() === FactoryTypeEnum::TYPE_LOGICS) {
								$chain->setLogicsFactory($factory);
							}
						}

						if (is_array($factory)) {

							if (empty($factory)) {
								continue;
							}

							if ($factory[0] instanceof ParserInterface) {
								$chain->addParsers($factory);
							}
						}

						if (is_string($factory)) {
							if ($this->checkValidatorClass($factory)) {
								$chain->setValidatorClass($factory);
							}
						}
					}
				}

				return $chain;
			}
		};
	}
}