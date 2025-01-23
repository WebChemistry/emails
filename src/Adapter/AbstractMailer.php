<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\Exception\NotSupportedException;
use WebChemistry\Emails\Mailer;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;

readonly abstract class AbstractMailer implements Mailer
{

	/**
	 * @param EmailAccount[] $recipients
	 * @param mixed[] $options Mailer specific options
	 */
	public function send(array $recipients, Message $message, array $options = []): void
	{
		throw NotSupportedException::method(static::class, __METHOD__);
	}

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
	): void
	{
		throw NotSupportedException::method(static::class, __METHOD__);
	}

}
