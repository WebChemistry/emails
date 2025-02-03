<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Section;

use WebChemistry\Emails\Model\SubscriptionModel;

final class SectionSubscriptionMap
{

	public function __construct(
		private SubscriptionModel $subscriptionModel,
		private Section $section,
		private string $email,
	)
	{
	}

	/**
	 * @return array<string, bool>
	 */
	public function get(): array
	{
		return $this->subscriptionModel->getSectionArrayOfBooleans($this->email, $this->section);
	}

	/**
	 * @param array<string, bool> $values
	 */
	public function set(array $values): void
	{
		$this->subscriptionModel->updateSectionByArrayOfBooleans($this->email, $this->section, $values);
	}

}
