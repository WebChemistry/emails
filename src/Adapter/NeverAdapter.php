<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use LogicException;
use WebChemistry\Emails\MailerAdapter;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;

final readonly class NeverAdapter implements MailerAdapter
{

	public function send(array $recipients, Message $message, array $options = []): void
	{
		throw new LogicException('No mailer adapter configured. Configure transactional or marketing adapter in the emails bundle.');
	}

	public function operate(
		array $accounts,
		array $groups = [],
		OperationType $type = OperationType::Insert,
		array $options = [],
	): void
	{
		throw new LogicException('No mailer adapter configured. Configure transactional or marketing adapter in the emails bundle.');
	}

}