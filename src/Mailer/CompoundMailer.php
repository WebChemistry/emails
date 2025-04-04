<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Mailer;

use WebChemistry\Emails\Mailer;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;
use WebChemistry\Emails\Section\SectionCategory;

final readonly class CompoundMailer implements Mailer
{

	public function __construct(
		private Mailer $transactional,
		private Mailer $marketing,
	)
	{
	}

	public function send(array $recipients, Message $message, string $section, string $category = SectionCategory::Global, array $options = []): void
	{
		$this->transactional->send($recipients, $message, $section, $category, $options);
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
