<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Link;

use WebChemistry\Emails\Section\SectionCategory;

interface SubscribeLinkGenerator
{

	public function unsubscribe(string $email, string $section, string $category = SectionCategory::Global): ?string;

	public function resubscribe(string $email, string $section, string $category = SectionCategory::Global): ?string;

	public function canUse(string $section, string $category = SectionCategory::Global): bool;

	public function load(string $link): DecodedResubscribeValue|DecodedUnsubscribeValue|null;

}
