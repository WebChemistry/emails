<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Section;

use InvalidArgumentException;
use WebChemistry\Emails\EmailManager;

final readonly class SectionConfig
{

	public const MaxLength = 30;

	/** @var string[] */
	private array $categories;

	/**
	 * @param string[] $categories
	 */
	public function __construct(
		public string $name,
		array $categories = [],
		private bool $unsubscribable = true,
	)
	{
		if (str_contains($name, '.')) {
			throw new InvalidArgumentException('Section name cannot contain dot.');
		}

		if (strlen($name) > self::MaxLength) {
			throw new InvalidArgumentException(sprintf('Section name cannot be longer than %d characters.', self::MaxLength));
		}

		foreach ($categories as $category) {
			if (str_contains($category, '.')) {
				throw new InvalidArgumentException('Category name cannot contain dot.');
			}

			if (strlen($category) > self::MaxLength) {
				throw new InvalidArgumentException(sprintf('Category name cannot be longer than %d characters.', self::MaxLength));
			}
		}

		if (in_array(EmailManager::GlobalCategory, $categories, true)) {
			throw new InvalidArgumentException(sprintf('Global category "%s" is reserved.', EmailManager::GlobalCategory));
		}

		$this->categories = $categories;
	}

	public function hasCategory(string $name): bool
	{
		if ($name === EmailManager::GlobalCategory) {
			return true;
		}

		return in_array($name, $this->categories, true);
	}

	public function hasCategories(): bool
	{
		return count($this->categories) > 1;
	}

	/**
	 * @return string[]
	 */
	public function getCategories(): array
	{
		return $this->categories;
	}

	public function isUnsubscribable(): bool
	{
		return $this->unsubscribable;
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

		$missing = array_diff($this->categories, $categories);
		$extra = array_diff($categories, $this->categories);

		if ($missing && $extra) {
			throw new InvalidArgumentException(sprintf('Categories %s are missing and %s are extra in section %s.', implode(', ', $missing), implode(', ', $extra), $this->name));
		}

		if ($missing) {
			throw new InvalidArgumentException(sprintf('Categories %s are missing in section %s.', implode(', ', $missing), $this->name));
		}

		throw new InvalidArgumentException(sprintf('Categories %s are extra in section %s.', implode(', ', $extra), $this->name));
	}

}
