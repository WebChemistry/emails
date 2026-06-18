<?php declare(strict_types = 1);

namespace Tests\Section;

use InvalidArgumentException;
use Tests\TestCase;
use WebChemistry\Emails\Section\Section;
use WebChemistry\Emails\Section\SectionCategory;

final class SectionTest extends TestCase
{

	public function testHasCategory(): void
	{
		$section = new Section('newsletter', ['daily', 'weekly']);

		$this->assertTrue($section->hasCategory('daily'));
		$this->assertTrue($section->hasCategory('weekly'));
		$this->assertFalse($section->hasCategory('monthly'));
	}

	public function testGlobalCategoryAlwaysExists(): void
	{
		$section = new Section('newsletter');

		$this->assertTrue($section->hasCategory(SectionCategory::Global));
		$this->assertFalse($section->hasCategories());
	}

	public function testHasCategories(): void
	{
		$this->assertFalse((new Section('newsletter'))->hasCategories());
		$this->assertTrue((new Section('newsletter', ['daily']))->hasCategories());
	}

	public function testDuplicateCategoryThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Category daily is duplicated.');

		new Section('newsletter', ['daily', 'daily']);
	}

	public function testReservedGlobalCategoryThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage(sprintf('Category %s is reserved.', SectionCategory::Global));

		new Section('newsletter', [SectionCategory::Global]);
	}

	public function testGetCategory(): void
	{
		$section = new Section('newsletter', ['daily']);

		$this->assertSame('daily', $section->getCategory('daily')->name);
	}

	public function testGetGlobalCategory(): void
	{
		$section = new Section('newsletter');

		$this->assertTrue($section->getGlobalCategory()->isGlobal());
		$this->assertTrue($section->getCategory(SectionCategory::Global)->isGlobal());
	}

	public function testGetUnknownCategoryThrows(): void
	{
		$section = new Section('newsletter', ['daily']);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Category weekly does not exist in section newsletter.');

		$section->getCategory('weekly');
	}

	public function testIsEssential(): void
	{
		$this->assertTrue((new Section(Section::Essential))->isEssential());
		$this->assertFalse((new Section('newsletter'))->isEssential());
	}

	public function testIsUnsubscribable(): void
	{
		$this->assertTrue((new Section('newsletter'))->isUnsubscribable());
		$this->assertFalse((new Section('newsletter', [], false))->isUnsubscribable());
	}

	public function testValidateCategoriesPasses(): void
	{
		$section = new Section('newsletter', ['daily', 'weekly']);

		$section->validateCategories(['daily', 'weekly']);

		$this->expectNotToPerformAssertions();
	}

	public function testValidateCategoriesUnknownThrows(): void
	{
		$section = new Section('newsletter', ['daily']);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Category weekly does not exist in section newsletter.');

		$section->validateCategories(['weekly']);
	}

	public function testValidateCategoriesMissingThrows(): void
	{
		$section = new Section('newsletter', ['daily', 'weekly']);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Categories weekly are missing in section newsletter.');

		$section->validateCategories(['daily']);
	}

	public function testValidateCategoriesIgnoresDuplicatesWhenComplete(): void
	{
		$section = new Section('newsletter', ['daily', 'weekly']);

		$section->validateCategories(['daily', 'weekly', 'daily']);

		$this->expectNotToPerformAssertions();
	}

	public function testValidateCategoriesDoesNotMaskMissingByDuplicates(): void
	{
		$section = new Section('newsletter', ['daily', 'weekly']);

		// A duplicate must not let the count match and hide that 'weekly' is missing.
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Categories weekly are missing in section newsletter.');

		$section->validateCategories(['daily', 'daily']);
	}

}
