<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Section;

use InvalidArgumentException;

final class Sections
{

	/** @var array<string, Section> */
	private array $sections = [];

	public function __construct()
	{
		$this->sections[Section::Essential] = new Section(Section::Essential, unsubscribable: false);
	}

	/**
	 * @return Section[]
	 */
	public function getAll(): array
	{
		return $this->sections;
	}

	public function getEssentialCategory(): SectionCategory
	{
		return $this->getCategory(Section::Essential);
	}

	public function getEssentialSection(): Section
	{
		return $this->getSection(Section::Essential);
	}

	public function add(SectionBlueprint $blueprint): void
	{
		$section = $blueprint->createSection();

		if (isset($this->sections[$section->name])) {
			throw new InvalidArgumentException(sprintf('Section %s already exists.', $section->name));
		}

		$this->sections[$section->name] = $section;
	}

	/**
	 * Whether a section of this name is configured. Callers that take a section name from outside the
	 * application - a webhook payload, a stored value - should ask before getSection(), which throws.
	 */
	public function has(string $section): bool
	{
		return isset($this->sections[$section]);
	}

	public function getSection(string $section): Section
	{
		return $this->sections[$section] ?? throw new InvalidArgumentException(sprintf('Section %s does not exist.', $section));
	}

	public function getCategory(string $section, string $category = SectionCategory::Global): SectionCategory
	{
		return $this->getSection($section)->getCategory($category);
	}

}
