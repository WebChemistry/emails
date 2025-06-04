<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Token;

use LogicException;

final readonly class StaticTokenProvider implements TokenProvider
{

	public function __construct(
		private string $token,
	)
	{
	}

	public function getToken(): Token
	{
		return new Token($this->token);
	}

	public function update(): never
	{
		throw new LogicException('StaticTokenProvider does not support token updates.');
	}

}
