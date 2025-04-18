<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use InvalidArgumentException;
use WebChemistry\Emails\MailerAdapter;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;

final readonly class SelectiveAdapter implements MailerAdapter
{

	public const OptionKey = 'section';

	private MailerAdapter $defaultMailer;

	/**
	 * @param array<string, MailerAdapter> $mailers
	 */
	public function __construct(
		private array $mailers,
	)
	{
		$this->defaultMailer = $this->mailers['default'] ?? throw new InvalidArgumentException('Default mailer not found.');
	}

	public function send(array $recipients, Message $message, array $options = []): void
	{
		if (!isset($options[self::OptionKey])) {
			$this->defaultMailer->send($recipients, $message, $options);
		} else {
			$mailer = $this->mailers[$options[self::OptionKey]] ?? throw new InvalidArgumentException(
				sprintf('Mailer %s not found, possible mailers: %s', $options[self::OptionKey], implode(', ', array_keys($this->mailers))),
			);

			$mailer->send($recipients, $message, $options);
		}
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
