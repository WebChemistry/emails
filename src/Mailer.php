<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

interface Mailer
{

	/**
	 * @param EmailAccount[] $recipients
	 * @param mixed[] $options Mailer specific options
	 */
	public function send(
		array $recipients,
		Message $message,
		string $section,
		string $category = EmailManager::GlobalCategory,
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
