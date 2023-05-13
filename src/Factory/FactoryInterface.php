<?php 

namespace AjdVal\Factory;

use Closure;

interface FactoryInterface
{
	 /**
     * Initialize the Factory.
     * @param  string  $abstract
     * @param  \Closure|null  $callback
     * @param  array  $paramaters
     *
     * @return void
     */
    public function generate(string $abstract, Closure|null $callback = null, ...$paramaters);

    /**
     * Set a namespace.
     *
     * @param  string  $namespace
     * @return $this
     */
    public function setNamespace(string $namespace): FactoryInterface;

    /**
     * Set a directory.
     *
     * @param  string  $directory
     * @return $this
     */
    public function setDirectory(string $directory): FactoryInterface;

    /**
     * Set a factory type.
     *
     * @param  FactoryTypeEnum  $factoryType
     * @return $this
     */
    public function setFactoryType(FactoryTypeEnum $factoryType): FactoryInterface;

    /**
     * append namespace to array.
     *
     * @param  string  $namespace
     * @return $this
     */
    public function appendNamespace(string $namespace): FactoryInterface;

    /**
     * prepend namespace to array.
     *
     * @param  string  $namespace
     * @return $this
     */
    public function prependNamespace(string $namespace): FactoryInterface;

    /**
     * append directory to array.
     *
     * @param  string  $directory
     * @return $this
     */
    public function appendDirectory(string $directory): FactoryInterface;

    /**
     * prepend directory to array.
     *
     * @param  string  $directory
     * @return $this
     */
    public function prependDirectory(string $directory): FactoryInterface;

    /**
     * append namespaces to array.
     *
     * @param  string  $namespace
     * @return $this
     */
    public function appendNamespaces(array $namespaces): FactoryInterface;

    /**
     * prepend namespaces to array.
     *
     * @param  string  $namespace
     * @return $this
     */
    public function prependNamespaces(array $namespaces): FactoryInterface;

    /**
     * append directories to array.
     *
     * @param  string  $directory
     * @return $this
     */
    public function appendDirectories(array $directories): FactoryInterface;

    /**
     * prepend directories to array.
     *
     * @param  string  $directory
     * @return $this
     */
    public function prependDirectories(array $directories): FactoryInterface;

    /**
     * Get factory Type.
     *
     * @return string
     */
    public function getFactoryType(): string;

     /**
     * Get the namespaces.
     *
     * @return array
     */
    public function getNamespaces(): array;

     /**
     * Get the directories.
     *
     * @return array
     */
    public function getDirectories(): array;

    /**
     * Do not throw error when class is not found.
     *
     * @return static
     */
    public function allowNotFound(): static;

    /**
     * Get allowNotFound property.
     *
     * @return bool
     */
    public function getAllowNotFound(): bool;
}