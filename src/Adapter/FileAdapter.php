<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use Nette\Utils\FileSystem;
use RuntimeException;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\EmailAccountWithFields;
use WebChemistry\Emails\HtmlMessage;
use WebChemistry\Emails\Mailer;
use WebChemistry\Emails\MailerAdapter;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;
use WebChemistry\Emails\SubscriberEmailAccount;
use WebChemistry\Emails\TemplateMessage;

final readonly class FileAdapter implements MailerAdapter
{

	private const int MaxLoggedRecipients = 10;
	private const int MaxLoggedTextLength = 500;

	public function __construct(
		private string $directory,
		private ?string $logFile = null,
	)
	{
	}

	public function send(array $recipients, Message $message, array $options = []): void
	{
		$date = $this->getDate();
		$type = null;
		$extras = [];

		$uniqueId = uniqid();

		if ($message instanceof HtmlMessage) {
			$type = 'html';
			$fileName = sprintf('send_%s.%s.html', $date, $uniqueId);
			$filePath = sprintf('%s/%s', $this->directory, $fileName);

			$extras['file'] = $fileName;

			FileSystem::write($filePath, $message->getBody());
		} else if ($message instanceof TemplateMessage) {
			$type = 'template';
			$extras['template'] = $message->getTemplate();
		}

		$values = [
			'type' => $type,
			'recipients' => array_map(fn (EmailAccount $acc) => $this->processAccount($acc), $recipients),
			'message' => [
				'sender' => ($sender = $message->getSender()) ? $sender->toString() : null,
				'subject' => $message->getSubject(),
				...$extras,
			],
			'options' => $this->cleanOptions($options),
		];


		$generator = $options[Mailer::UnsubscribeGeneratorOption] ?? null;

		if ($generator) {
			$links = [];

			foreach ($recipients as $recipient) {
				$links[$recipient->email] = $generator($recipient->email);
			}

			$values['unsubscribe_links'] = $links;
		}

		$json = $this->encode($values);

		FileSystem::write(sprintf('%s/send_%s.%s.json', $this->directory, $date, $uniqueId), $json);

		$this->appendLog($this->createSendLogValues($date, $type, $recipients, $message, $extras));
	}

	public function operate(
		array $accounts,
		array $groups = [],
		OperationType $type = OperationType::Insert,
		array $options = [],
	): void
	{
		$json = $this->encode([
			'accounts' => array_map(fn (EmailAccount $acc) => $this->processAccount($acc), $accounts),
			'groups' => $groups,
			'type' => $type->name,
			'options' => $options,
		]);

		FileSystem::write(sprintf('%s/operation_%s.%s.json', $this->directory, $this->getDate(), uniqid()), $json);
	}

	/**
	 * @param mixed[] $values
	 */
	private function appendLog(array $values): void
	{
		if ($this->logFile === null) {
			return;
		}

		FileSystem::createDir(dirname($this->logFile));

		$result = file_put_contents($this->logFile, sprintf("%s\n", $this->encode($values, false)), FILE_APPEND | LOCK_EX);

		if ($result === false) {
			throw new RuntimeException(sprintf('Cannot write email log file %s.', $this->logFile));
		}
	}

	/**
	 * @param mixed[] $options
	 * @return mixed[]
	 */
	private function cleanOptions(array $options): array
	{
		foreach ($options as $key => $value) {
			if (is_array($value)) {
				$options[$key] = $this->cleanOptions($value);
			} else if (!is_scalar($value) && $value !== null) {
				unset($options[$key]);
			}
		}

		return $options;
	}

	/**
	 * @param mixed[] $values
	 */
	private function encode(array $values, bool $pretty = true): string
	{
		$options = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

		if ($pretty) {
			$options |= JSON_PRETTY_PRINT;
		}

		return json_encode(
			$values,
			$options,
		);
	}

	private function getDate(): string
	{
		return date('Y-m-d_H-i-s');
	}

	/**
	 * @return string|mixed[]
	 */
	private function processAccount(EmailAccount $account): string|array
	{
		if ($account instanceof EmailAccountWithFields) {
			return [
				'email' => $account->toString(),
				'fields' => $account->fields,
			];
		}

		if ($account instanceof SubscriberEmailAccount) {
			return [
				'email' => $account->toString(),
				'fields' => $account->fields,
				'options' => $account->options,
			];
		}

		return $account->toString();
	}

	/**
	 * @param EmailAccount[] $recipients
	 * @param array<string, string> $extras
	 * @return mixed[]
	 */
	private function createSendLogValues(
		string $date,
		?string $type,
		array $recipients,
		Message $message,
		array $extras,
	): array
	{
		$values = [
			'sent_at' => $date,
			'type' => $type,
			'recipients' => $this->processLogRecipients($recipients),
			'message' => [
				'sender' => ($sender = $message->getSender()) ? $sender->toString() : null,
				'subject' => $this->shortenText($message->getSubject()),
				...$extras,
			],
		];

		if ($message instanceof HtmlMessage) {
			$bodyPreview = preg_replace('/\s+/', ' ', trim(strip_tags($message->getBody())));

			$values['message']['body_preview'] = $this->shortenText($bodyPreview ?? '');
		}

		return $values;
	}

	/**
	 * @param EmailAccount[] $recipients
	 * @return array{count: int, items: list<string>, truncated: bool}
	 */
	private function processLogRecipients(array $recipients): array
	{
		$loggedRecipients = array_values(array_slice($recipients, 0, self::MaxLoggedRecipients));

		return [
			'count' => count($recipients),
			'items' => array_map(fn (EmailAccount $account): string => $account->toString(), $loggedRecipients),
			'truncated' => count($recipients) > self::MaxLoggedRecipients,
		];
	}

	private function shortenText(?string $text): ?string
	{
		if ($text === null || strlen($text) <= self::MaxLoggedTextLength) {
			return $text;
		}

		return sprintf('%s...', substr($text, 0, self::MaxLoggedTextLength));
	}

}
