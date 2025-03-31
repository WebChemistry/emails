<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Token;

use SensitiveParameter;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final class NinjaMailTesterDoctrineTokenProvider extends DoctrineTokenProvider
{

	public const string Id = 'mail_tester_ninja';

	public function __construct(
		#[SensitiveParameter]
		private readonly string $secret,
		TokenRepository $tokenRepository,
		private readonly HttpClientInterface $httpClient,
		private readonly bool $strict = false,
	)
	{
		parent::__construct($tokenRepository);
	}

	protected function getId(): string
	{
		return self::Id;
	}

	protected function requestToken(): ?string
	{
		try {
			$response = $this->httpClient->request('GET', 'https://token.mailtester.ninja/token', [
				'query' => [
					'key' => $this->secret,
				],
			]);
			$values = $response->toArray(false);
			$token = $values['token'] ?? null;

			return is_string($token) ? $token : null;
		} catch (Throwable $exception) {
			if ($this->strict) {
				throw $exception;
			}

			return null;
		}
	}

}
