<?php 

namespace AjdVal\Contracts;

use AjdVal\Context;
use AjdVal\ValidatorDto;

interface RuleInterface
{
	/**
     * Validates the value against set of rules.
     *
     * @param  mixed  $value
     * @param  string  $path
     * @return bool
     */
	public function validate(mixed $value, string $path = ''): bool;

    /**
     * Sets the Context Factory
     *
     * @param  \AjdVal\Context\ContextFactoryInterface  $contextFactory
     * @return void
     */
    public function setContextFactory(Context\ContextFactoryInterface $contextFactory): void;

    /**
     * Sets the Context
     *
     * @param  \AjdVal\Context\ContextInterface  $context
     * @return void
     */
    public function setContext(Context\ContextInterface $context): void;

     /**
     * Sets the Context
     *
     * @param  \AjdVal\ValidatorDto  $context
     * @return void
     */
    public function setValidatorDto(ValidatorDto $validatorDto): void;

    /**
     * Gets the Context Factory
     *
     * @return \AjdVal\Context\ContextFactoryInterface
     */
    public function getContextFactory(): Context\ContextFactoryInterface;

    /**
     * Gets the Context
     *
     * @return \AjdVal\Context\ContextInterface
     */
    public function getContext(): Context\ContextInterface;
}