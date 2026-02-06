<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

use WebChemistry\Emails\Exception\HttpClientException;

interface MailerAdapter
{

	/**
	 * @param EmailAccount[] $recipients
	 * @param mixed[] $options Mailer specific options
	 *
	 * @throws HttpClientException
	 */
	public function send(array $recipients, Message $message, array $options = []): void;

	/**
	 * @param EmailAccount[] $accounts
	 * @param string[] $groups
	 * @param mixed[] $options Mailer specific options
	 *
	 * @throws HttpClientException
	 */
	public function operate(
		array $accounts,
		array $groups = [],
		OperationType $type = OperationType::Insert,
		array $options = [],
	): void;

}
