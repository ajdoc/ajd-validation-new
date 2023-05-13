<?php 

namespace AjdVal\Parsers;

use AjDic\AjDic;
use AjdVal\Parsers\Metadata\ClassMetadata;

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
    public function setValditor($validator): void;

    /**
     * Gets the validator
     *
     * @return object
     */
    public function getValidator(): object|null; 
}