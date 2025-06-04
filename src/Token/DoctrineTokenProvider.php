<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Token;

use LogicException;
use Symfony\Contracts\Service\ResetInterface;

abstract class DoctrineTokenProvider implements ResetInterface, TokenProvider
{

	private ?Token $token = null;

	public function __construct(
		private readonly TokenRepository $tokenRepository,
	)
	{
	}

	abstract protected function getId(): string;

	abstract protected function requestToken(): ?string;

	final public function getToken(): Token
	{
		if ($token = $this->token) {
			return $token;
		}

		return $this->retrieveToken();
	}

	private function retrieveToken(): Token
	{
		$token = $this->tokenRepository->getToken($this->getId());

		if ($token === null) {
			$token = $this->update();
		}

		return $this->token = $token;
	}

	final public function update(): Token
	{
		$token = $this->requestToken();

		if ($token === null) {
			throw new LogicException(sprintf('Cannot retrieve token for %s.', $this->getId()));
		}

		$this->token = new Token($token);
		$this->tokenRepository->upsert($this->getId(), $token);

		return $this->token;
	}

	public function reset(): void
	{
		$this->token = null;
	}

}
