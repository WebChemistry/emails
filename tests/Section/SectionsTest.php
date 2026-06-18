<?php declare(strict_types = 1);

namespace Tests\Section;

use InvalidArgumentException;
use Tests\TestCase;
use WebChemistry\Emails\Section\Section;
use WebChemistry\Emails\Section\SectionBlueprint;
use WebChemistry\Emails\Section\SectionCategory;
use WebChemistry\Emails\Section\Sections;

final class SectionsTest extends TestCase
{

	public function testEssentialSectionExistsByDefault(): void
	{
		$sections = new Sections();

		$essential = $sections->getEssentialSection();

		$this->assertSame(Section::Essential, $essential->name);
		$this->assertTrue($essential->isEssential());
		$this->assertFalse($essential->isUnsubscribable());
		$this->assertSame([Section::Essential], array_keys($sections->getAll()));
	}

	public function testGetEssentialCategoryReturnsGlobal(): void
	{
		$sections = new Sections();

		$this->assertTrue($sections->getEssentialCategory()->isGlobal());
	}

	public function testAddSection(): void
	{
		$sections = new Sections();
		$sections->add(new SectionBlueprint('newsletter', ['daily']));

		$section = $sections->getSection('newsletter');

		$this->assertSame('newsletter', $section->name);
		$this->assertTrue($section->hasCategory('daily'));
		$this->assertSame([Section::Essential, 'newsletter'], array_keys($sections->getAll()));
	}

	public function testAddDuplicateSectionThrows(): void
	{
		$sections = new Sections();
		$sections->add(new SectionBlueprint('newsletter'));

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Section newsletter already exists.');

		$sections->add(new SectionBlueprint('newsletter'));
	}

	public function testAddingEssentialSectionThrows(): void
	{
		$sections = new Sections();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage(sprintf('Section %s already exists.', Section::Essential));

		$sections->add(new SectionBlueprint(Section::Essential));
	}

	public function testGetUnknownSectionThrows(): void
	{
		$sections = new Sections();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Section newsletter does not exist.');

		$sections->getSection('newsletter');
	}

	public function testGetCategory(): void
	{
		$sections = new Sections();
		$sections->add(new SectionBlueprint('newsletter', ['daily']));

		$this->assertSame('daily', $sections->getCategory('newsletter', 'daily')->name);
		$this->assertTrue($sections->getCategory('newsletter')->isGlobal());
	}

	public function testGetCategoryDefaultsToGlobal(): void
	{
		$sections = new Sections();
		$sections->add(new SectionBlueprint('newsletter'));

		$category = $sections->getCategory('newsletter');

		$this->assertSame(SectionCategory::Global, $category->name);
	}

}
