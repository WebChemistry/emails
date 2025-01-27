<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Validator;

use SensitiveParameter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MailerTesterNinjaValidator implements EmailValidator
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
		$response = $this->client->request('GET', sprintf('https://happy.mailtester.ninja/ninja?email=%s&token=%s', $email, $this->secret));
		$payload = $response->toArray();
		$message = $payload['message'] ?? null;
		$ok = in_array($message, ['Accepted', 'Catch-All', 'Limited', 'MX Error', 'Timeout'], true);

		return new ValidationResult($ok, $message, self::class);
	}

}
