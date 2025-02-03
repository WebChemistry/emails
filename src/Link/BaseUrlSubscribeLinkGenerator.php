<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Link;

use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Section\SectionCategory;
use WebChemistry\Emails\Section\Sections;

final readonly class BaseUrlSubscribeLinkGenerator implements SubscribeLinkGenerator
{

	use SubscribeLinkGeneratorTrait;

	public function __construct(
		private string $baseUrl,
		private Sections $sections,
		private Encoder $encoder,
	)
	{
	}

	public function unsubscribe(string $email, string $section, string $category = SectionCategory::Global): ?string
	{
		$category = $this->sections->getCategory($section, $category);

		if (!$category->isUnsubscribable()) {
			return null;
		}

		if ($category->section->unsubscribeAllCategories) {
			$category = $category->section->getGlobalCategory();
		}

		return $this->addUnsubscribeQueryParameter($this->baseUrl, $email, $category);
	}

	public function resubscribe(string $email, string $section, string $category = SectionCategory::Global): ?string
	{
		$category = $this->sections->getCategory($section, $category);

		if (!$category->isUnsubscribable()) {
			return null;
		}

		if ($category->section->unsubscribeAllCategories) {
			$category = $category->section->getGlobalCategory();
		}

		return $this->addResubscribeQueryParameter($this->baseUrl, $email, $category);
	}

	public function canUse(string $section, string $category = SectionCategory::Global): bool
	{
		$category = $this->sections->getCategory($section, $category);

		return $category->isUnsubscribable();
	}

	public function load(string $link): DecodedResubscribeValue|DecodedUnsubscribeValue|null
	{
		return $this->loadQueryParameter($link);
	}

}
