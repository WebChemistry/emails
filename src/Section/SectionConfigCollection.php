<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Section;

use InvalidArgumentException;

final readonly class SectionConfigCollection
{

	/** @var array<class-string, object> */
	private array $configs;

	/**
	 * @param object[] $configs
	 */
	public function __construct(array $configs = [])
	{
		$this->configs = $this->parseConfigs($configs);
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T
	 */
	public function get(string $className): object
	{
		if (!isset($this->configs[$className])) {
			throw new InvalidArgumentException(sprintf('Config %s is not defined.', $className));
		}

		/** @var T */
		return $this->configs[$className];
	}

	/**
	 * @template T of object
	 * @param class-string<T> $className
	 * @return T|null
	 */
	public function getOrNull(string $className): ?object
	{
		/** @var T|null */
		return $this->configs[$className] ?? null;
	}

	/**
	 * @param class-string $className
	 */
	public function has(string $className): bool
	{
		return isset($this->configs[$className]);
	}

	/**
	 * @param object[] $configs
	 * @return array<class-string, object>
	 */
	private function parseConfigs(array $configs): array
	{
		$index = [];

		foreach ($configs as $config) {
			if (isset($index[$config::class])) {
				throw new InvalidArgumentException(sprintf('Config %s is already defined.', $config::class));
			}

			$index[$config::class] = $config;
		}

		return $index;
	}

}
