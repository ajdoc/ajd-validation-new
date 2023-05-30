<?php 

namespace AjdVal\Parsers;

use AjDic\AjDic;
use AjdVal\Contracts\RuleInterface;
use AjdVal\Rules;
use AjdVal\Parsers\Metadata\ClassMetadata;
use AjdVal\Validators\ValidatorsInterface;
use AjdVal\Contracts\RuleIsExpressionInterface;

interface ParserInterface
{
	/**
     * try and load the Metadata of the class
     *
     * @param  string  $class
     * @return bool
     */
	public function loadMetadata(ClassMetadata $class): array;

    /**
     * Sets the container
     *
     * @param  \AjDic\AjDic  $container
     * @return void
     */
    public function setContainer(AjDic $container): void;

    /**
     * Gets the container
     *
     * @return \AjDic\AjDic
     */
    public function getContainer(): AjDic|null;

    /**
     * Sets the validator
     *
     * @param    $validator
     * @return void
     */
    public function setValidator(ValidatorsInterface $validator): void;

    /**
     * Gets the validator
     *
     * @return object
     */
    public function getValidator(): ValidatorsInterface|null; 

     /**
     * Resolved The Rule To Expression
     * @param    RuleInterface $instance
     * @param    string $name
     * @param    array $arguments
     * @param    AjDic\AjDic|null $container
     * @param    AjdVal\Validators\ValidatorsInterface|null $validator
     *
     * @return AjdVAl\Rules\Expr
     */
    public function resolveToExpr(
        RuleInterface $instance, 
        string $name, 
        array $arguments = [], 
        AjDic|null $container = null, 
        ValidatorsInterface|null $validator = null
    ): RuleIsExpressionInterface;
    
}