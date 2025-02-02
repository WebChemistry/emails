<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Section;

use InvalidArgumentException;
use WebChemistry\Emails\EmailManager;

final class Sections
{

	/** @var array<string, SectionConfig> */
	private array $sections = [];

	public function __construct()
	{
		$this->sections[EmailManager::SectionEssential] = new SectionConfig(EmailManager::SectionEssential, unsubscribable: false);
	}

	public function addSection(SectionConfig $section): void
	{
		if (isset($this->sections[$section->name])) {
			throw new InvalidArgumentException(sprintf('Section %s already exists.', $section->name));
		}

		$this->sections[$section->name] = $section;
	}

	public function getConfig(string $section): SectionConfig
	{
		if (!isset($this->sections[$section])) {
			throw new InvalidArgumentException(sprintf('Section %s does not exist.', $section));
		}

		return $this->sections[$section];
	}

	public function getSectionCategory(string $section, string $category): SectionCategory
	{
		if (!isset($this->sections[$section])) {
			throw new InvalidArgumentException(sprintf('Section %s does not exist.', $section));
		}

		$section = $this->sections[$section];

		if (!$section->hasCategory($category)) {
			throw new InvalidArgumentException(sprintf('Category %s does not exist in section %s.', $section->name, $category));
		}

		return new SectionCategory($section->name, $category, $section->isUnsubscribable());
	}

	/**
	 * @return array{string, string}
	 */
	private function parse(string $fullName): array
	{
		$pos = strpos($fullName, '.');

		if ($pos === false) {
			return [$fullName, EmailManager::GlobalCategory];
		}

		return [substr($fullName, 0, $pos), substr($fullName, $pos + 1)];
	}

	public function validateSection(string $section): void
	{
		if (!isset($this->sections[$section])) {
			throw new InvalidArgumentException(sprintf('Section %s does not exist.', $section));
		}
	}

	public function validateFullName(string $fullName): void
	{
		[$main, $sub] = $this->parse($fullName);

		if (!isset($this->sections[$main])) {
			throw new InvalidArgumentException(sprintf('Section %s does not exist.', $main));
		}

		$section = $this->sections[$main];

		if (!$section->hasCategory($sub)) {
			throw new InvalidArgumentException(sprintf('Category %s does not exist in section %s.', $sub, $main));
		}
	}

}
