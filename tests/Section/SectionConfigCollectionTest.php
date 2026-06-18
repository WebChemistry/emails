<?php declare(strict_types = 1);

namespace Tests\Section;

use InvalidArgumentException;
use stdClass;
use Tests\TestCase;
use WebChemistry\Emails\Section\SectionConfigCollection;

final class SectionConfigCollectionTest extends TestCase
{

	public function testGetReturnsConfig(): void
	{
		$config = new FirstConfig();
		$collection = new SectionConfigCollection([$config]);

		$this->assertSame($config, $collection->get(FirstConfig::class));
	}

	public function testGetUndefinedConfigThrows(): void
	{
		$collection = new SectionConfigCollection();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage(sprintf('Config %s is not defined.', FirstConfig::class));

		$collection->get(FirstConfig::class);
	}

	public function testGetOrNull(): void
	{
		$config = new FirstConfig();
		$collection = new SectionConfigCollection([$config]);

		$this->assertSame($config, $collection->getOrNull(FirstConfig::class));
		$this->assertNull($collection->getOrNull(SecondConfig::class));
	}

	public function testHas(): void
	{
		$collection = new SectionConfigCollection([new FirstConfig()]);

		$this->assertTrue($collection->has(FirstConfig::class));
		$this->assertFalse($collection->has(SecondConfig::class));
	}

	public function testIndexesByClassName(): void
	{
		$first = new FirstConfig();
		$second = new SecondConfig();
		$collection = new SectionConfigCollection([$first, $second]);

		$this->assertSame($first, $collection->get(FirstConfig::class));
		$this->assertSame($second, $collection->get(SecondConfig::class));
	}

	public function testDuplicateConfigClassThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage(sprintf('Config %s is already defined.', stdClass::class));

		new SectionConfigCollection([new stdClass(), new stdClass()]);
	}

}

final class FirstConfig
{

}

final class SecondConfig
{

}
