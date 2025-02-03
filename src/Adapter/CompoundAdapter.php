<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use WebChemistry\Emails\MailerAdapter;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;

final readonly class CompoundAdapter implements MailerAdapter
{

	public function __construct(
		private MailerAdapter $transactional,
		private MailerAdapter $marketing,
	)
	{
	}

	public function send(array $recipients, Message $message, array $options = []): void
	{
		$this->transactional->send($recipients, $message, $options);
	}

	public function operate(
		array $accounts,
		array $groups = [],
		OperationType $type = OperationType::Insert,
		array $options = [],
	): void
	{
		$this->marketing->operate($accounts, $groups, $type, $options);
	}

}
