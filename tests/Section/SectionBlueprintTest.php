<?php declare(strict_types = 1);

namespace Tests\Section;

use InvalidArgumentException;
use Tests\TestCase;
use WebChemistry\Emails\Section\SectionBlueprint;

final class SectionBlueprintTest extends TestCase
{

	public function testCreateSection(): void
	{
		$blueprint = new SectionBlueprint('newsletter', ['daily', 'weekly'], false);

		$section = $blueprint->createSection();

		$this->assertSame('newsletter', $section->name);
		$this->assertTrue($section->hasCategory('daily'));
		$this->assertTrue($section->hasCategory('weekly'));
		$this->assertFalse($section->isUnsubscribable());
	}

	public function testSectionNameAtMaxLengthIsAllowed(): void
	{
		$name = str_repeat('a', SectionBlueprint::MaxLength);

		$blueprint = new SectionBlueprint($name);

		$this->assertSame($name, $blueprint->createSection()->name);
	}

	public function testTooLongSectionNameThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage(sprintf('Section name cannot be longer than %d characters.', SectionBlueprint::MaxLength));

		new SectionBlueprint(str_repeat('a', SectionBlueprint::MaxLength + 1));
	}

	public function testTooLongCategoryNameThrowsOnCreateSection(): void
	{
		// Category names are validated lazily by a generator, so construction must not throw.
		$blueprint = new SectionBlueprint('newsletter', [str_repeat('a', SectionBlueprint::MaxLength + 1)]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage(sprintf('Category name cannot be longer than %d characters.', SectionBlueprint::MaxLength));

		$blueprint->createSection();
	}

}
