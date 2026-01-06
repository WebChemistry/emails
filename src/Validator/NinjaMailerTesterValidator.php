<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Validator;

use SensitiveParameter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class NinjaMailerTesterValidator implements EmailValidator
{

	private RetryableHttpClient $client;

	public function __construct(
		#[SensitiveParameter]
		private string $secret,
		?HttpClientInterface $client = null,
		int $retryDelayMs = 1000,
	)
	{
		$this->client = new RetryableHttpClient(
			$client ?? HttpClient::create(),
			new GenericRetryStrategy(delayMs: $retryDelayMs, multiplier: 1.0, jitter: 0.0),
		);
	}

	/**
	 * @param mixed[] $options
	 */
	public function validate(string $email, array $options = []): ValidationResult
	{
		return $this->send($email);
	}

	private function send(string $email): ValidationResult
	{
		$response = $this->client->request('GET', sprintf('https://happy.mailtester.ninja/ninja?email=%s&key=%s', $email, $this->secret));
		$payload = $response->toArray();
		$message = $payload['message'] ?? null;

		$ok = in_array($message, ['Accepted', 'Catch-All', 'Limited', 'MX Error', 'Timeout'], true);

		return new ValidationResult($ok, $message, self::class);
	}

}
