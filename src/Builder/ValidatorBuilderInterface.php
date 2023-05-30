<?php 

namespace AjdVal\Builder;

use AjdVal\Factory\FactoryInterface;
use AjdVal\Parsers\ParserInterface;

interface ValidatorBuilderInterface
{
	/**
     * Set the Rules Factory.
     * @param  \AjdVal\Factory\FactoryInterface  $rulesFactory
     * 
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setRulesFactory(FactoryInterface $rulesFactory): ValidatorBuilderInterface;

    /**
     * Set the Filters Factory.
     * @param  \AjdVal\Factory\FactoryInterface  $filtersFactory
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setFiltersFactory(FactoryInterface $filtersFactory): ValidatorBuilderInterface;

    /**
     * Set the Rules Handler Factory.
     * @param  \AjdVal\Factory\FactoryInterface  $rulesHandlerFactory
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setRulesHandlerFactory(FactoryInterface $rulesHandlerFactory): ValidatorBuilderInterface;

    /**
     * Set the Rules Exception Factory.
     * @param  \AjdVal\Factory\FactoryInterface  $rulesHandlerFactory
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setRulesExceptionFactory(FactoryInterface $rulesExceptionFactory): ValidatorBuilderInterface;

    /**
     * Set the Logics Factory.
     * @param  \AjdVal\Factory\FactoryInterface  $logicsFactory
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setLogicsFactory(FactoryInterface $logicsFactory): ValidatorBuilderInterface;


    /**
     * Get the Rules Factory.
     *
     * @return \AjdVal\Factory\FactoryInterface
     */
    public function getRulesFactory(): FactoryInterface|null;

    /**
     * Get the Filters Factory.
     *
     * @return \AjdVal\Factory\FactoryInterface
     */
    public function getFiltersFactory(): FactoryInterface|null;

    /**
     * Get the Rules Handler Factory.
     *
     * @return \AjdVal\Factory\FactoryInterface
     */
    public function getRulesHandlerFactory(): FactoryInterface|null;

    /**
     * Get the Rules Exception Factory.
     *
     * @return \AjdVal\Factory\FactoryInterface
     */
    public function getRulesExceptionFactory(): FactoryInterface|null;

    /**
     * Get the Logics Factory.
     *
     * @return \AjdVal\Factory\FactoryInterface
     */
    public function getLogicsFactory(): FactoryInterface|null;

    /**
     * Add Parser
     * @param  \AjdVal\Parsers\ParserInterface  $parser
     *
     * @return \AjdVal\Builder\ValidatorBuilderInterface
     */
    public function addParser(ParserInterface $parser): ValidatorBuilderInterface;

    /**
     * Add Parser
     * @param  array  $parsers
     *
     * @return \AjdVal\Builder\ValidatorBuilderInterface
     */
    public function addParsers(array $parsers): ValidatorBuilderInterface;

    /**
     * Get Parsers
     *
     * @return array
     */
    public function getParsers(): array;    

    /**
     * Set the Validator Class
     * @param  \AjdVal\Validators\ValidatorsInterface::class|null $validator
     *
     * @return \AjdVal\Builder\ValidatorBuilderInterface
     */
    public function setValidatorClass(string|null $validator = null): ValidatorBuilderInterface;

    /**
     * Get the Validator Class
     *
     * @return \AjdVal\Validators\ValidatorsInterface
     */
    public function getValidatorClass(): string;

    /**
     * Set the Validation Class
     * @param  \AjdVal\Validators\ValidatorsInterface::class|null $validation
     *
     * @return \AjdVal\Builder\ValidatorBuilderInterface
     */
    public function setValidationClass(string|null $validation = null): ValidatorBuilderInterface;

    /**
     * Get the Validation Class
     *
     * @return \AjdVal\Validations\ValidationInterface
     */
    public function getValidationClass(): string;
}