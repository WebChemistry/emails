<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use WebChemistry\Emails\Adapter\InMemory\InMemorySentEmail;
use WebChemistry\Emails\MailerAdapter;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;

final class InMemoryAdapter implements MailerAdapter
{

	/** @var InMemorySentEmail[] */
	private array $sent = [];

	/**
	 * @return InMemorySentEmail[]
	 */
	public function getSent(): array
	{
		return $this->sent;
	}

	public function send(array $recipients, Message $message, array $options = []): void
	{
		foreach ($recipients as $recipient) {
			$this->sent[] = new InMemorySentEmail($recipient, $message, $options);
		}
	}

	public function operate(
		array $accounts,
		array $groups = [],
		OperationType $type = OperationType::Insert,
		array $options = [],
	): void
	{
	}

}
