<?php declare(strict_types = 1);

namespace Tests\Section;

use Tests\TestCase;
use WebChemistry\Emails\Section\Section;
use WebChemistry\Emails\Section\SectionCategory;

final class SectionCategoryTest extends TestCase
{

	public function testIsGlobal(): void
	{
		$section = new Section('newsletter', ['daily']);

		$this->assertTrue($section->getGlobalCategory()->isGlobal());
		$this->assertFalse($section->getCategory('daily')->isGlobal());
	}

	public function testIsUnsubscribableDelegatesToSection(): void
	{
		$unsubscribable = new Section('newsletter', ['daily']);
		$locked = new Section('locked', ['daily'], false);

		$this->assertTrue($unsubscribable->getCategory('daily')->isUnsubscribable());
		$this->assertFalse($locked->getCategory('daily')->isUnsubscribable());
	}

	public function testAccessMultidimensionalArrayForGlobalCategory(): void
	{
		$category = (new Section('newsletter'))->getGlobalCategory();

		$array = ['newsletter' => [SectionCategory::Global => 'global-value']];

		$this->assertSame('global-value', $category->accessMultidimensionalArray($array));
		$this->assertNull($category->accessMultidimensionalArray([]));
	}

	public function testAccessMultidimensionalArrayForNamedCategoryPrefersGlobal(): void
	{
		$category = (new Section('newsletter', ['daily']))->getCategory('daily');

		$withGlobal = ['newsletter' => [SectionCategory::Global => 'global-value', 'daily' => 'daily-value']];
		$withoutGlobal = ['newsletter' => ['daily' => 'daily-value']];

		$this->assertSame('global-value', $category->accessMultidimensionalArray($withGlobal));
		$this->assertSame('daily-value', $category->accessMultidimensionalArray($withoutGlobal));
		$this->assertNull($category->accessMultidimensionalArray([]));
	}

}
