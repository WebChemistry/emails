<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Section;

use InvalidArgumentException;

final readonly class Section
{

	public const Essential = 'essential';

	public const MaxLength = 30;

	/** @var array<string, SectionCategory> */
	private array $categories;

	/**
	 * @param iterable<string> $categoryNames
	 */
	public function __construct(
		public string $name,
		iterable $categoryNames = [],
		private bool $unsubscribable = true,
		public bool $unsubscribeAllCategories = true,
	)
	{
		$categories = [];

		foreach ($categoryNames as $category) {
			if (isset($categories[$category])) {
				throw new InvalidArgumentException(sprintf('Category %s is duplicated.', $category));
			}

			if ($category === SectionCategory::Global) {
				throw new InvalidArgumentException(sprintf('Category %s is reserved.', SectionCategory::Global));
			}

			$categories[$category] = new SectionCategory($this, $category);
		}

		$this->categories = $categories;
	}

	public function hasCategory(string $name): bool
	{
		return $name === SectionCategory::Global || isset($this->categories[$name]);
	}

	public function hasCategories(): bool
	{
		return (bool) $this->categories;
	}

	/**
	 * @return array<string, SectionCategory>
	 */
	public function getCategories(): array
	{
		return $this->categories;
	}

	public function getCategory(string $name): SectionCategory
	{
		if ($name === SectionCategory::Global) {
			return new SectionCategory($this, SectionCategory::Global);
		}

		return $this->categories[$name] ?? throw new InvalidArgumentException(sprintf('Category %s does not exist in section %s.', $name, $this->name));
	}

	public function isUnsubscribable(): bool
	{
		return $this->unsubscribable;
	}

	public function isEssential(): bool
	{
		return $this->name === self::Essential;
	}

	/**
	 * @param string[] $categories
	 */
	public function validateCategories(array $categories): void
	{
		foreach ($categories as $category) {
			if (!$this->hasCategory($category)) {
				throw new InvalidArgumentException(sprintf('Category %s does not exist in section %s.', $category, $this->name));
			}
		}

		if (count($categories) === count($this->categories)) {
			return;
		}

		$categoryNames = array_keys($this->categories);

		$missing = array_diff($categoryNames, $categories);
		$extra = array_diff($categories, $categoryNames);

		if ($missing && $extra) {
			throw new InvalidArgumentException(sprintf('Categories %s are missing and %s are extra in section %s.', implode(', ', $missing), implode(', ', $extra), $this->name));
		}

		if ($missing) {
			throw new InvalidArgumentException(sprintf('Categories %s are missing in section %s.', implode(', ', $missing), $this->name));
		}

		throw new InvalidArgumentException(sprintf('Categories %s are extra in section %s.', implode(', ', $extra), $this->name));
	}

	public function getGlobalCategory(): SectionCategory
	{
		return $this->getCategory(SectionCategory::Global);
	}

}
