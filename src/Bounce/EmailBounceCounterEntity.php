<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Bounce;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;

trait EmailBounceCounterEntity // @phpstan-ignore-line
{

	public const TableName = 'email_bounce_counters';

	#[Id]
	#[Column(type: 'string', length: 255)]
	private string $email;

	#[Column(type: 'integer')]
	private int $bounceCount = 0;

	public function __construct(string $email)
	{
		$this->email = $email;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getBounceCount(): int
	{
		return $this->bounceCount;
	}

	public function increment(): void
	{
		$this->bounceCount++;
	}

}
