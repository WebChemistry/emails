<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Mailer;

use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Mailer;
use WebChemistry\Emails\MailerAdapter;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;

final readonly class ManagedMailer implements Mailer
{

	public function __construct(
		private MailerAdapter $adapter,
		private EmailManager $manager,
	)
	{
	}

	public function send(
		array $recipients,
		Message $message,
		string $section,
		string $category = EmailManager::GlobalCategory,
		array $options = [],
	): void
	{
		$recipients = $this->manager->filterEmailAccountsForDelivery($recipients, $section, $category);

		if (!$recipients) {
			return;
		}

		$this->adapter->send($recipients, $message, $options);
	}

	public function operate(
		array $accounts,
		array $groups = [],
		OperationType $type = OperationType::Insert,
		array $options = [],
	): void
	{
		$this->adapter->operate($accounts, $groups, $type, $options);
	}

}
