<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;

trait EmailBounceCounterEntity // @phpstan-ignore trait.unused
{

	public const string TableName = 'email_bounce_counters';

	#[Id]
	#[Column(type: 'string', length: 255)]
	private string $email;

	#[Column(type: 'integer', options: ['default' => '0'])]
	private int $counter = 0;

	public function __construct(string $email)
	{
		$this->email = $email;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getCounter(): int
	{
		return $this->counter;
	}

	public function increment(): void
	{
		$this->counter++;
	}

}
