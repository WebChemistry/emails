<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Validator;

use Symfony\Component\Clock\DatePoint;
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

		// refresh is required every 24 hours
		$min = new DatePoint('- 23 hours');

		if ($token->createdAt < $min) {
			$token = $this->tokenProvider->update();
		}

		$response = $this->client->request('GET', sprintf('https://happy.mailtester.ninja/ninja?email=%s&token=%s', $email, $token->value));
		$payload = $response->toArray();
		$message = $payload['message'] ?? null;

		if ($message === 'Token Timeout') {
			if (!$isRetry) {
				$this->tokenProvider->update();
			}

			return $this->send($email, true);
		}

		$ok = in_array($message, ['Accepted', 'Catch-All', 'Limited', 'MX Error', 'Timeout'], true);

		return new ValidationResult($ok, $message, self::class);
	}

}
