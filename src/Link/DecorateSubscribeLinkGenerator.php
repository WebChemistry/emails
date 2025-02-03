<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Link;

use WebChemistry\Emails\Section\SectionCategory;

abstract readonly class DecorateSubscribeLinkGenerator implements SubscribeLinkGenerator
{

	public function __construct(
		protected SubscribeLinkGenerator $decorate,
	)
	{
	}

	public function unsubscribe(string $email, string $section, string $category = SectionCategory::Global): ?string
	{
		return $this->decorate->unsubscribe($email, $section, $category);
	}

	public function resubscribe(string $email, string $section, string $category = SectionCategory::Global): ?string
	{
		return $this->decorate->resubscribe($email, $section, $category);
	}

	public function canUse(string $section, string $category = SectionCategory::Global): bool
	{
		return $this->decorate->canUse($section, $category);
	}

	public function load(string $link): DecodedResubscribeValue|DecodedUnsubscribeValue|null
	{
		return $this->decorate->load($link);
	}

}
