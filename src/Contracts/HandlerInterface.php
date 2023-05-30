<?php 

namespace AjdVal\Contracts;

interface HandlerInterface
{
	 /**
     * Sets the Rule
     *
     * @param  \AjdVal\Contracts\RuleInterface  $rule
     * @return void
     */
	public function setRuleObject(RuleInterface $rule) :void;

	 /**
     * Gets the Rule
     *
     * @return \AjdVal\Contracts\RuleInterface
     */
	public function getRuleObject(): RuleInterface;

	 /**
     * Gets Handler Name
     *
     * @return string
     */
	public function getName(): string;

	 /**
     * Function Done Before Rule Initialization
     *
     * @param  array  $arguments
     * @return array
     */
	public function preInit(array $arguments = []): array;

	 /**
     * Function Done Before Validation
     *
     * @param  array  $details
     * @return array
     */
	public function preCheck(array $details): array;

	/**
     * Function Done After Validation
     * 
     * @param  array|bool  $result
     * @param  array  $details
     * @return array
     */
	public function postCheck(array|bool $result, array $details): array;

	 /**
     * Sets the Previous Handler
     *
     * @param  \AjdVal\Contracts\HandlerInterface  $handler
     * @return void
     */
	public function setPreviousHandler(HandlerInterface $handler): void;

	 /**
     * Gets the Previous Handler
     *
     * @return  \AjdVal\Contracts\HandlerInterface
     */
	public function getPreviousHandler(): HandlerInterface;

	 /**
     * Clears the previous Handler property
     *
     * @return  void
     */
	public function clearPreviousHandler(): void;

	public function __set($name, $value);

	public function __get($name);

	public function __isset($name);
}