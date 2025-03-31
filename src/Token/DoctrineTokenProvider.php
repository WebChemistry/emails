<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Token;

use RuntimeException;
use Symfony\Contracts\Service\ResetInterface;

abstract class DoctrineTokenProvider implements ResetInterface, TokenProvider
{

	private ?string $token = null;

	public function __construct(
		private readonly TokenRepository $tokenRepository,
	)
	{
	}

	abstract protected function getId(): string;

	abstract protected function requestToken(): ?string;

	final public function getToken(): string
	{
		if ($token = $this->token) {
			return $token;
		}

		return $this->retrieveToken();
	}

	private function retrieveToken(): string
	{
		$token = $this->tokenRepository->getToken($this->getId());

		if ($token === null) {
			$token = $this->update();

			if ($token === null) {
				throw new RuntimeException(sprintf('Cannot retrieve token for %s.', $this->getId()));
			}
		}

		return $this->token = $token;
	}

	final public function update(): ?string
	{
		$token = $this->requestToken();

		if ($token === null) {
			return null;
		}

		$this->token = $token;
		$this->tokenRepository->upsert($this->getId(), $token);

		return $token;
	}

	public function reset(): void
	{
		$this->token = null;
	}

}
