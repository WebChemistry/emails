<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Token;

interface TokenProvider
{

	public function getToken(): Token;

	public function update(): Token;

}
