<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Section;

use WebChemistry\Emails\Model\SubscriptionModel;

final class DefaultSectionSubscriptionMap implements SectionSubscriptionMap
{

	public function __construct(
		private SubscriptionModel $subscriptionModel,
		private Section $section,
		private string $email,
	)
	{
	}

	public function get(): array
	{
		return $this->subscriptionModel->getSectionArrayOfBooleans($this->email, $this->section);
	}

	public function set(array $values): void
	{
		$this->subscriptionModel->updateSectionByArrayOfBooleans($this->email, $this->section, $values);
	}

}
