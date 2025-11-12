<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Validator;

use SensitiveParameter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class NinjaMailerTesterValidator implements EmailValidator
{

	public function __construct(
		#[SensitiveParameter]
		private string $secret,
		private HttpClientInterface $client,
	)
	{
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
