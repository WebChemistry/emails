<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Section;

use InvalidArgumentException;

final readonly class SectionBlueprint
{

	public const MaxLength = 30;

	/**
	 * @param string[] $categories
	 */
	public function __construct(
		private string $name,
		private array $categories = [],
		private bool $unsubscribable = true,
	)
	{
		if (strlen($this->name) > self::MaxLength) {
			throw new InvalidArgumentException(sprintf('Section name cannot be longer than %d characters.', self::MaxLength));
		}
	}

	/**
	 * @param string[] $categories
	 * @return iterable<string>
	 */
	private function getCategoryNames(array $categories): iterable
	{
		foreach ($categories as $category) {
			if (strlen($category) > self::MaxLength) {
				throw new InvalidArgumentException(sprintf('Category name cannot be longer than %d characters.', self::MaxLength));
			}

			yield $category;
		}
	}

	public function createSection(): Section
	{
		return new Section($this->name, $this->getCategoryNames($this->categories), $this->unsubscribable);
	}

}
