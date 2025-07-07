<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use SensitiveParameter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\EmailAccountWithFields;
use WebChemistry\Emails\OperationType;

final readonly class MailerliteAdapter extends AbstractAdapter
{

	/** @deprecated  */
	public const Autoresponders = 'autoresponders';
	/** @deprecated  */
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

		// MailerLite API supports max 50 requests per batch
		foreach (array_chunk($accounts, 50) as $chunk) {
			$requests = [];

			/** @var EmailAccount $account */
			foreach ($chunk as $account) {
				$requests[] = [
					'method' => 'POST',
					'path' => '/api/subscribers',
					'body' => $this->accountToArray($account, $groups),
				];
			}

			$this->sendApiRequest('POST', '/api/batch', [
				'requests' => $requests,
			])->getContent(); // calling getContent() just to throw exception if response is not successful
		}
	}

	/**
	 * @param string[] $groups
	 * @return mixed[]
	 */
	private function accountToArray(EmailAccount $account, array $groups = []): array
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

		if ($groups) {
			$values['groups'] = $groups;
		}

		return $values;
	}

	/**
	 * @param mixed[] $body
	 */
	private function sendApiRequest(string $method, string $path, array $body = []): ResponseInterface
	{
		$options = [
			'headers' => [
				'Authorization' => sprintf('Bearer %s', $this->secret),
			],
		];

		if ($body) {
			$options['json'] = $body;
		}

		return $this->client->request($method, 'https://connect.mailerlite.com' . $path, $options);
	}

}
