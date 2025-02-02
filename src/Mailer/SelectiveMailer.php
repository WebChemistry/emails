<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Mailer;

use InvalidArgumentException;
use WebChemistry\Emails\Mailer;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;
use WebChemistry\Emails\Section\SectionCategory;

final readonly class SelectiveMailer implements Mailer
{
	public const OptionKey = 'select';

	private Mailer $defaultMailer;

	/**
	 * @param array<string, Mailer> $mailers
	 */
	public function __construct(
		private array $mailers,
	)
	{
		$this->defaultMailer = $this->mailers['default'] ?? throw new InvalidArgumentException('Default mailer not found.');
	}

	public function send(
		array $recipients,
		Message $message,
		string $section,
		string $category = SectionCategory::Global,
		array $options = [],
	): void
	{
		if (isset($options[self::OptionKey])) {
			$mailer = $this->mailers[$options[self::OptionKey]] ?? throw new InvalidArgumentException(
				sprintf('Mailer %s not found, possible mailers: %s', $options[self::OptionKey], implode(', ', array_keys($this->mailers))),
			);
		} else if (isset($this->mailers[$section])) {
			$mailer = $this->mailers[$section];
		} else {
			$mailer = $this->defaultMailer;
		}

		$mailer->send($recipients, $message, $section, $category, $options);
	}

	public function operate(
		array $accounts,
		array $groups = [],
		OperationType $type = OperationType::Insert,
		array $options = [],
	): void
	{
		if (!isset($options[self::OptionKey])) {
			$this->defaultMailer->operate($accounts, $groups, $type, $options);
		} else {
			$mailer = $this->mailers[$options[self::OptionKey]] ?? throw new InvalidArgumentException(
				sprintf('Mailer %s not found, possible mailers: %s', $options[self::OptionKey], implode(', ', array_keys($this->mailers))),
			);

			$mailer->operate($accounts, $groups, $type, $options);
		}
	}

}
