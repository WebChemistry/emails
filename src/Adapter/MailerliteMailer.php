<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use SensitiveParameter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\EmailAccountWithFields;
use WebChemistry\Emails\OperationType;

final readonly class MailerliteMailer extends AbstractMailer
{

	public const Autoresponders = 'autoresponders';
	public const Resubscribe = 'resubscribe';

	public function __construct(
		#[SensitiveParameter]
		private string $secret,
		private HttpClientInterface $client,
	)
	{
	}

	public function operate(
		array $accounts,
		array $groups = [],
		OperationType $type = OperationType::Insert,
		array $options = [],
	): void
	{
		if (!$accounts) {
			return;
		}

		if ($groups) {
			$responses = [];

			foreach ($groups as $group) {
				$responses[] = $this->sendApiRequest('POST', '/api/v2/groups/' . $group . '/subscribers/import', [
					'subscribers' => array_map(fn (EmailAccount $account) => $this->accountToArray($account), $accounts),
					...$this->getImportOptions($options, $type),
				]);
			}

			foreach ($responses as $response) {
				$response->getContent(); // calling getContent() just to throw exception if response is not successful
			}

			return;
		}

		// MailerLite API supports max 50 requests per batch
		foreach (array_chunk($accounts, 50) as $chunk) {
			$requests = [];

			/** @var EmailAccount $account */
			foreach ($chunk as $account) {
				$requests[] = [
					'method' => 'POST',
					'path' => '/api/v2/subscribers',
					'body' => $this->accountToArray($account),
				];
			}

			$this->sendApiRequest('POST', '/api/v2/batch', [
				'requests' => $requests,
			])->getContent(); // calling getContent() just to throw exception if response is not successful
		}
	}

	/**
	 * @return mixed[]
	 */
	private function accountToArray(EmailAccount $account): array
	{
		$values = [
			'email' => $account->email,
		];

		if ($account->name !== null && $account->name !== '') {
			$values['name'] = $account->name;
		}

		if ($account instanceof EmailAccountWithFields && $account->fields) {
			$values['fields'] = $account->fields;
		}

		return $values;
	}

	/**
	 * @param mixed[] $options
	 * @return mixed[]
	 */
	private function getImportOptions(array $options, OperationType $type): array
	{
		$vals = [];

		if (isset($options[self::Resubscribe])) {
			$vals[self::Resubscribe] = $options[self::Resubscribe];
		} else {
			$vals[self::Resubscribe] = $type === OperationType::Insert;
		}

		if (isset($options[self::Autoresponders])) {
			$vals[self::Autoresponders] = $options[self::Autoresponders];
		} else {
			$vals[self::Autoresponders] = $type === OperationType::Insert;
		}

		return $vals;
	}

	/**
	 * @param mixed[] $body
	 */
	private function sendApiRequest(string $method, string $path, array $body = []): ResponseInterface
	{
		$options = [
			'headers' => [
				'X-MailerLite-ApiKey' => $this->secret,
			],
		];

		if ($body) {
			$options['json'] = $body;
		}

		return $this->client->request($method, 'https://api.mailerlite.com' . $path, $options);
	}

}
