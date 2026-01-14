<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use Nette\Utils\FileSystem;
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

	public function __construct(
		private string $directory,
	)
	{
	}

	public function send(array $recipients, Message $message, array $options = []): void
	{
		$date = $this->getDate();
		$type = null;
		$extras = [];

		if ($message instanceof HtmlMessage) {
			$type = 'html';
			$fileName = 'send_' . $date . '.' . uniqid() . '.html';
			$filePath = $this->directory . '/' . $fileName;

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

		FileSystem::write($this->directory . '/send_' . $date . '.' . uniqid() . '.json', $json);
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

		FileSystem::write($this->directory . '/operation_' . $this->getDate() . '.' . uniqid() . '.json', $json);
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
	private function encode(array $values): string
	{
		return json_encode(
			$values,
			JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_PRETTY_PRINT,
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

}
