<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Subscription;

use DateTimeImmutable;
use InvalidArgumentException;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Section\Sections;
use WebChemistry\Emails\Type\UnsubscribeType;

final class SubscriptionInfo
{

	/**
	 * @param array<string, array<string, array{ type: UnsubscribeType, createdAt: DateTimeImmutable }>> $index
	 */
	private function __construct(
		private array $index,
		private Sections $sections,
	)
	{
	}

	/**
	 * @return array<string, bool>
	 */
	public function getCategoriesAsMapOfBooleans(string $section): array
	{
		$config = $this->sections->getConfig($section);

		if (!$config->hasCategories()) {
			throw new InvalidArgumentException(sprintf('Section %s does not have categories.', $section));
		}

		$categories = [];

		if (isset($this->index[$section][EmailManager::GlobalCategory])) {
			foreach ($config->getCategories() as $category) {
				$categories[$category] = false;
			}
		} else {
			foreach ($config->getCategories() as $category) {
				$categories[$category] = !isset($this->index[$section][$category]);
			}
		}

		return $categories;
	}

	public function getReason(string $section, string $category = EmailManager::GlobalCategory): ?UnsubscribeType
	{
		$section = $this->sections->getSectionCategory($section, $category);

		$value = $section->accessMultidimensionalArray($this->index);

		if ($value === null) {
			return null;
		}

		return $value['type'];
	}

	/**
	 * @param array{ section: string, category: string, type: string, created_at: string }[] $results
	 */
	public static function fromResults(array $results, Sections $sections): self
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

		return new self($index, $sections);
	}

}
