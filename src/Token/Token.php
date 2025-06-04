<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Token;

use DateTimeImmutable;
use Symfony\Component\Clock\DatePoint;

final readonly class Token
{

	public function __construct(
		public string $value,
		public DateTimeImmutable $createdAt = new DatePoint(),
	)
	{
	}

}
