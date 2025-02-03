<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

use WebChemistry\Emails\Section\SectionCategory;

interface Mailer
{

	public const SectionOption = '_section';
	public const CategoryOption = '_category';

	/**
	 * @param EmailAccount[] $recipients
	 * @param mixed[] $options Mailer specific options
	 */
	public function send(
		array $recipients,
		Message $message,
		string $section,
		string $category = SectionCategory::Global,
		array $options = [],
	): void;

	/**
	 * @param EmailAccount[] $accounts
	 * @param string[] $groups
	 * @param mixed[] $options Mailer specific options
	 */
	public function operate(
		array $accounts,
		array $groups = [],
		OperationType $type = OperationType::Insert,
		array $options = [],
	): void;

}
