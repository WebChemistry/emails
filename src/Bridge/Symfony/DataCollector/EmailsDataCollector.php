<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Bridge\Symfony\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Throwable;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\HtmlMessage;
use WebChemistry\Emails\Mailer;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\OperationType;

final class EmailsDataCollector extends DataCollector implements LateDataCollectorInterface
{

	public const Name = 'webchemistry_emails';

	/**
	 * @var list<array{recipients: list<string>, subject: string|null, sender: string|null, section: string|null, category: string|null, body: string|null}>
	 */
	private array $sent = [];

	/**
	 * @var list<array{accounts: list<string>, groups: list<string>, type: string}>
	 */
	private array $operations = [];

	public function __construct()
	{
		$this->data = [
			'sent' => [],
			'operations' => [],
		];
	}

	/**
	 * @param EmailAccount[] $recipients
	 * @param mixed[] $options
	 */
	public function collectSentEmail(array $recipients, Message $message, array $options): void
	{
		$this->sent[] = [
			'recipients' => array_map(
				static fn (EmailAccount $account): string => $account->toString(),
				array_values($recipients),
			),
			'subject' => $message->getSubject(),
			'sender' => $message->getSender()?->toString(),
			'section' => $options[Mailer::SectionOption] ?? null,
			'category' => $options[Mailer::CategoryOption] ?? null,
			'body' => $message instanceof HtmlMessage ? $message->getBody() : null,
		];
	}

	/**
	 * @param EmailAccount[] $accounts
	 * @param string[] $groups
	 */
	public function collectOperation(array $accounts, array $groups, OperationType $type): void
	{
		$this->operations[] = [
			'accounts' => array_map(
				static fn (EmailAccount $account): string => $account->toString(),
				array_values($accounts),
			),
			'groups' => array_values($groups),
			'type' => $type->name,
		];
	}

	public function collect(Request $request, Response $response, ?Throwable $exception = null): void
	{
	}

	public function lateCollect(): void
	{
		$this->data = [
			'sent' => $this->sent,
			'operations' => $this->operations,
		];
	}

	public function getName(): string
	{
		return self::Name;
	}

	/**
	 * @return list<array{recipients: list<string>, subject: string|null, sender: string|null, section: string|null, category: string|null, body: string|null}>
	 */
	public function getSentEmails(): array
	{
		return $this->data['sent'];
	}

	/**
	 * @return list<array{accounts: list<string>, groups: list<string>, type: string}>
	 */
	public function getOperations(): array
	{
		return $this->data['operations'];
	}

	public function getSentCount(): int
	{
		$count = 0;

		foreach ($this->data['sent'] as $sent) {
			$count += count($sent['recipients']);
		}

		return $count;
	}

	public function reset(): void
	{
		$this->sent = [];
		$this->operations = [];
		$this->data = [
			'sent' => [],
			'operations' => [],
		];
	}

}
