<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Bridge\Symfony;

use WebChemistry\Emails\Bridge\Symfony\DataCollector\EmailsDataCollector;
use WebChemistry\Emails\MailerAdapter;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;

final readonly class ProfilerMailerAdapter implements MailerAdapter
{

	public function __construct(
		private MailerAdapter $adapter,
		private EmailsDataCollector $collector,
	)
	{
	}

	public function send(array $recipients, Message $message, array $options = []): void
	{
		$this->collector->collectSentEmail($recipients, $message, $options);

		$this->adapter->send($recipients, $message, $options);
	}

	public function operate(
		array $accounts,
		array $groups = [],
		OperationType $type = OperationType::Insert,
		array $options = [],
	): void
	{
		$this->collector->collectOperation($accounts, $groups, $type);

		$this->adapter->operate($accounts, $groups, $type, $options);
	}

}
