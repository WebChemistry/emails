<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Validator;

use SensitiveParameter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use WebChemistry\Emails\Token\TokenProvider;

final readonly class NinjaMailerTesterValidator implements EmailValidator
{

	public function __construct(
		private TokenProvider $tokenProvider,
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

	private function send(string $email, bool $isRetry = false): ValidationResult
	{
		$token = $this->tokenProvider->getToken();

		$response = $this->client->request('GET', sprintf('https://happy.mailtester.ninja/ninja?email=%s&token=%s', $email, $token));
		$payload = $response->toArray();
		$message = $payload['message'] ?? null;

		if ($message === 'Token Timeout') {
			$newToken = false;

			if (!$isRetry) {
				$newToken = $this->tokenProvider->update();
			}

			if (!$newToken) {
				trigger_error('Mail tester ninja validator: Token Timeout', E_USER_WARNING);

				return new ValidationResult(true, $message, self::class);
			}

			return $this->send($email, true);
		}

		$ok = in_array($message, ['Accepted', 'Catch-All', 'Limited', 'MX Error', 'Timeout'], true);

		return new ValidationResult($ok, $message, self::class);
	}

}
