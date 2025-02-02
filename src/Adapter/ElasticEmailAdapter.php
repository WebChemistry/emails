<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use InvalidArgumentException;
use SensitiveParameter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\EmailAccountWithFields;
use WebChemistry\Emails\Message;
use WebChemistry\Emails\TemplateMessage;

final readonly class ElasticEmailAdapter extends AbstractAdapter
{

	public function __construct(
		#[SensitiveParameter]
		private string $secret,
		private HttpClientInterface $client,
	)
	{
	}

	/**
	 * @param EmailAccount[] $recipients
	 * @param mixed[] $options Mailer specific options
	 */
	public function send(array $recipients, Message $message, array $options = []): void
	{
		$sender = $message->getSender();
		$subject = $message->getSubject();

		if (!$sender) {
			throw new InvalidArgumentException('Sender is required.');
		}

		if (!$message instanceof TemplateMessage) {
			throw new InvalidArgumentException(sprintf('Message must be instance of %s.', TemplateMessage::class));
		}

		$body = [
			'Recipients' => array_map($this->accountToArray(...), $recipients),
			'Content' => array_filter([
				'From' => $sender->toString(),
				'TemplateName' => $message->getTemplate(),
				'Subject' => $subject,
			], fn (mixed $val) => $val !== null),
		];

		if ($bodyOptions = $this->getOptions($options)) {
			$body['Options'] = $bodyOptions;
		}

		$this->sendApiRequest('POST', '/v4/emails', $body);
	}

	/**
	 * @return mixed[]
	 */
	private function accountToArray(EmailAccount $account): array
	{
		$values = [
			'Email' => $account->email,
		];

		$fields = [];

		if ($account instanceof EmailAccountWithFields && $account->fields) {
			$fields = $account->fields;
		}

		if ($account->name !== null && $account->name !== '') {
			$fields['name'] = $account->name;
		}

		if ($fields) {
			$values['Fields'] = $fields;
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
				'X-ElasticEmail-ApiKey' => $this->secret,
			],
		];

		if ($body) {
			$options['json'] = $body;
		}

		return $this->client->request($method, 'https://api.elasticemail.com' . $path, $options);
	}

	/**
	 * @param mixed[] $options
	 * @return mixed[]
	 */
	private function getOptions(array $options): array
	{
		$body = [];

		if (is_string($channel = $options['channel'] ?? null) && $channel !== '') {
			$body['ChannelName'] = $channel;
		}

		return $body;
	}

}
