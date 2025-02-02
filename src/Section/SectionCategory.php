<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Section;

use WebChemistry\Emails\EmailManager;

final readonly class SectionCategory
{

	public function __construct(
		public string $section,
		public string $category,
		public bool $unsubscribable,
	)
	{
	}

	/**
	 * @template T
	 * @param array<string, array<string, T>> $array
	 * @return T|null
	 */
	public function accessMultidimensionalArray(array $array): mixed
	{
		if ($this->category === EmailManager::GlobalCategory) {
			return $array[$this->section][EmailManager::GlobalCategory] ?? null;
		} else {
			return $array[$this->section][EmailManager::GlobalCategory] ?? $array[$this->section][$this->category] ?? null;
		}
	}

	public function isGlobal(): bool
	{
		return $this->category === EmailManager::GlobalCategory;
	}

}
