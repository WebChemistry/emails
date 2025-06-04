<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Token;

interface TokenRepository
{

	public function getToken(string $id): ?Token;

	public function upsert(string $id, string $token): void;

}
