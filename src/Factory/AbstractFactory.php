<?php 

namespace AjdVal\Factory;

use SplFileInfo;
use AjDic\AjDic;
use Loady\Finder;
use AjdVal\Utils;
use RuntimeException;
use InvalidArgumentException;
use Throwable;
use Closure;

abstract class AbstractFactory implements FactoryInterface
{
    protected bool $allowNotFound = false;

    protected array $namespaces = [];
    protected array $directories = [];

    protected string $factoryType;

    protected AjDic $container;
    protected Finder $finder;

    protected array $acceptFiles = ['*.php'];

    protected array $ignores = [];

	abstract public function initialize();

    public function __construct()
    {
        $this->container = new AjDic;
        $this->finder = new Finder;

        $this->ignores[] = dirname(__DIR__).Utils\Utils::DS.'Rules'.Utils\Utils::DS.'AbstractRule.php';
    }

    protected function checkFactory(): void
    {
        if (empty($this->namespaces)) {
            throw new RuntimeException('Namespaces must be set in initialize to generate.');
        }

        if (empty($this->directories)) {
            throw new RuntimeException('Directories must be set in initialize to generate.');
        }

        if (empty($this->factoryType)) {
            throw new RuntimeException('Factory Type must be set in initialize to generate.');
        }
    }

    protected function generateBaseName(SplFileInfo $file): string 
    {
        return $file->getBasename('.'.$file->getExtension());
    }

    protected function createFileIterator(string $dir, string|null $abstract = null): Finder
    {
        if (! is_dir($dir)) {
            throw new InvalidArgumentException(sprintf("File or directory '%s' not found.", $dir));
        }

        if (! empty($abstract)) {
            $abstract = \ucfirst($abstract).$this->factoryType;
        }

        $filter = function(SplFileInfo $file) use($abstract) {
            $baseName = $this->generateBaseName($file);
            $path = $file->getPathname();

            if (! empty($abstract)) {
                return ($file->isDir() 
                        || (str_ends_with($baseName, $this->factoryType) && $abstract === $baseName))
                        && !in_array($path, $this->ignores);
            }
            
            return ($file->isDir() 
                    || str_ends_with($baseName, $this->factoryType))
                    && !in_array($path, $this->ignores);
        };
        
        $iterator = $this->finder->findFiles($this->acceptFiles)
            ->filter($filter)
            ->descentFilter($filter)
            ->from($dir);

        return $iterator;
    }

    public function generate(string $abstract, Closure|null $callback = null, ...$paramaters): mixed
    {
        $this->checkFactory();

        foreach ($this->directories as $directory) {
            $iterator = is_file($directory)
                ? [new SplFileInfo($directory)]
                : $this->createFileIterator($directory, $abstract);

            if (empty($iterator->collect())) {
                if ($this->allowNotFound) {
                    return null;
                }

                throw new InvalidArgumentException(sprintf("Class '%s' %s type not found.", $abstract, $this->getFactoryType()));
            }

            foreach ($iterator as $fileInfo) {
                
                $baseName = $this->generateBaseName($fileInfo);
                $subDir = str_replace([$directory, '.'.$fileInfo->getExtension(), $baseName], ['', '', ''], $fileInfo->getPathname());
                
                foreach ($this->namespaces as $namespace) {
                    if (! empty($subDir)) {
                        $qualifiedClass = $namespace.$this->normalizeNamespace($subDir).$baseName;
                    } else {
                        $qualifiedClass = $namespace.$baseName;
                    }
                    
                    if (! class_exists($qualifiedClass)) {
                        continue;
                    }
                    
                    $callback = is_null($callback) ? function (
                        AjDic $container, 
                        string $qualifiedClass, 
                        array $paramaters, 
                        SplFileInfo $fileInfo, 
                        string $namespace
                    ) {
                        return $container->makeWith($qualifiedClass, $paramaters);
                    } : $callback;

                    $callback = $callback->bindTo($this, self::class);

                    return $callback($this->container, $qualifiedClass, $paramaters, $fileInfo, $namespace);
                }
            }        
        }
        
    }

    public function allowNotFound(): static 
    {
        $this->allowNotFound = true;

        return $this;
    }

    public function getAllowNotFound(): bool
    {
        return $this->allowNotFound;
    }

    public function setNamespace(string $namespace): FactoryInterface
    {
        $this->namespaces[] = $this->normalizeNamespace($namespace);

        return $this;
    }

    public function setDirectory(string $directory): FactoryInterface
    {
        $this->directories[] = $this->normalizeDirectory($directory);

        return $this;
    }
    
    public function setFactoryType(FactoryTypeEnum $factoryType): FactoryInterface
    {
        $this->factoryType = $factoryType->value;

        return $this;
    }

    public function getFactoryType(): string
    {
        return $this->factoryType;
    }

    public function appendNamespace(string $namespace): FactoryInterface
    {
        array_push($this->namespaces, $this->normalizeNamespace($namespace));

        return $this;
    }

    public function prependNamespace(string $namespace): FactoryInterface
    {
        array_unshift($this->namespaces, $this->normalizeNamespace($namespace));

        return $this;
    }

    public function appendNamespaces(array $namespaces): FactoryInterface
    {
        foreach ($namespaces as $namespace) {
            $this->appendNamespace($namespace);
        }

        return $this;
    }

    public function prependNamespaces(array $namespaces): FactoryInterface
    {
        foreach ($namespaces as $namespace) {
            $this->prependNamespace($namespace);
        }

        return $this;
    }

    public function appendDirectory(string $directory): FactoryInterface
    {
        array_push($this->directories, $this->normalizeDirectory($directory));

        return $this;
    }

    public function prependDirectory(string $directory): FactoryInterface
    {
        array_unshift($this->directories, $this->normalizeDirectory($directory));

        return $this;
    }

    public function appendDirectories(array $directories): FactoryInterface
    {
        foreach ($directories as $directory) {
            $this->appendDirectory($directory);
        }

        return $this;
    }

    public function prependDirectories(array $directories): FactoryInterface
    {
        foreach ($directories as $directory) {
            $this->prependDirectory($directory);
        }

        return $this;
    }

    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    public function getDirectories(): array
    {
        return $this->directories;
    }

    protected function normalizeNamespace(string $namespace): string 
    {
        return ! str_ends_with($namespace, '\\') ? $namespace.'\\' : $namespace;
    }

    protected function normalizeDirectory(string $directory): string 
    {
        return rtrim(str_replace(['\\', '/'], [Utils\Utils::DS, Utils\Utils::DS], $directory), Utils\Utils::DS).Utils\Utils::DS;
    }
}