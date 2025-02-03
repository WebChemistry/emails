<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Mailer;

use WebChemistry\Emails\EmailAccountRegistry;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Link\SubscribeLinkGenerator;
use WebChemistry\Emails\Mailer;
use WebChemistry\Emails\MailerAdapter;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;
use WebChemistry\Emails\Section\SectionCategory;

final readonly class ManagedMailer implements Mailer
{

	public function __construct(
		private MailerAdapter $adapter,
		private EmailManager $manager,
		private ?SubscribeLinkGenerator $subscribeLinkGenerator = null,
	)
	{
	}

	public function send(
		array $recipients,
		Message $message,
		string $section,
		string $category = SectionCategory::Global,
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

		$options[self::CategoryOption] = $category;
		$options[self::SectionOption] = $section;

		if ($this->subscribeLinkGenerator?->canUse($section, $category)) {
			$options[self::UnsubscribeGeneratorOption] = fn (string $email) => $this->subscribeLinkGenerator->unsubscribe($email, $section, $category);
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
