<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Section;

use InvalidArgumentException;
use WebChemistry\Emails\EmailManager;

final class Sections
{

	/** @var array<string, Section> */
	private array $sections = [];

	public function __construct()
	{
		$this->sections[EmailManager::SectionEssential] = new Section(EmailManager::SectionEssential, unsubscribable: false);
	}

	public function getEssentialCategory(): SectionCategory
	{
		return $this->getCategory(EmailManager::SectionEssential);
	}

	public function getEssentialSection(): Section
	{
		return $this->getSection(EmailManager::SectionEssential);
	}

	public function add(SectionBlueprint $blueprint): void
	{
		$section = $blueprint->createSection();

		if (isset($this->sections[$section->name])) {
			throw new InvalidArgumentException(sprintf('Section %s already exists.', $section->name));
		}

		$this->sections[$section->name] = $section;
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
