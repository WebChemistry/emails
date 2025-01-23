<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use WebChemistry\Emails\Mailer;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;

final readonly class CompoundMailer implements Mailer
{

	public function __construct(
		private Mailer $transactional,
		private Mailer $marketing,
	)
	{
	}

	public function send(array $recipients, Message $message, array $options = []): void
	{
		$this->transactional->send($recipients, $message);
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
