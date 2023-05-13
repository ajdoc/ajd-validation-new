<?php 

namespace AjdVal\Factory;

use InvalidArgumentException;

trait FactoryExtenderTrait 
{
	public static array $factoryMetadata = [
		'addedNamespaces' => 'appendNamespaces',
		'addedDirectories' => 'appendDirectories'
	];

	protected static array $metadata = [
		'addedNamespaces' => [],
		'addedDirectories' => []
	];

	public static function addNamespace(string $namespace): void 
	{
		static::$metadata['addedNamespaces'][] = $namespace;
	}

	public static function addDirectory(string $directory): void 
	{
		static::$metadata['addedDirectories'][] = $directory;
	}

	public static function append(FactoryInterface $factory): void
	{
		foreach (static::$factoryMetadata as $storage => $method) {
			if (empty(static::$metadata[$storage])) {
				continue;
			}	

			if (! method_exists($factory, $method)) {
				throw new InvalidArgumentException('Invalid Storage in Factory Type');
			}

			$factory->{$method}(static::$metadata[$storage]);
		}
	}
}