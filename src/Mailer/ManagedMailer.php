<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Mailer;

use WebChemistry\Emails\EmailAccountRegistry;
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
		$this->manager->beforeEmailSent(
			$registry = new EmailAccountRegistry($recipients),
			$section,
			$category,
		);

		if ($registry->isEmpty()) {
			return;
		}

		$this->adapter->send($registry->getAccounts(), $message, $options);

		$this->manager->afterEmailSent($registry, $section, $category);
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
