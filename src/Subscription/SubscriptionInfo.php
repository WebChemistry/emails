<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Subscription;

use DateTimeImmutable;
use InvalidArgumentException;
use WebChemistry\Emails\Section\Section;
use WebChemistry\Emails\Section\SectionCategory;
use WebChemistry\Emails\Type\UnsubscribeType;

final class SubscriptionInfo
{

	/**
	 * @param array<string, array<string, array{ type: UnsubscribeType, createdAt: DateTimeImmutable }>> $index
	 */
	private function __construct(
		private array $index,
		private Section $section,
	)
	{
	}

	/**
	 * @return array<string, bool>
	 */
	public function getCategoriesAsMapOfBooleans(): array
	{
		if (!$this->section->hasCategories()) {
			throw new InvalidArgumentException(sprintf('Section %s does not have categories.', $this->section->name));
		}

		$categories = [];

		if (isset($this->index[$this->section->name][SectionCategory::Global])) {
			foreach ($this->section->getCategories() as $category) {
				$categories[$category->name] = false;
			}
		} else {
			foreach ($this->section->getCategories() as $category) {
				$categories[$category->name] = !isset($this->index[$this->section->name][$category->name]);
			}
		}

		return $categories;
	}

	public function getReason(string $category = SectionCategory::Global): ?UnsubscribeType
	{
		$value = $this->section->getCategory($category)->accessMultidimensionalArray($this->index);

		if ($value === null) {
			return null;
		}

		return $value['type'];
	}

	/**
	 * @param array{ section: string, category: string, type: string, created_at: string }[] $results
	 */
	public static function fromResults(array $results, Section $section): self
	{
		$index = [];

		foreach ($results as $result) {
			$type = UnsubscribeType::from($result['type']);
			$createdAt = new DateTimeImmutable($result['created_at']);

			$index[$result['section']][$result['category']] = [
				'type' => $type,
				'createdAt' => $createdAt,
			];
		}

		return new self($index, $section);
	}

}
