<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use Symfony\Component\Clock\DatePoint;

trait TokenEntity // @phpstan-ignore trait.unused
{

	public const string TableName = 'tokens';

	#[Id]
	#[Column(type: 'string', length: 120)]
	private string $id;

	#[Column(type: 'string', length: 4096)]
	private string $token;

	#[Column(type: 'datetime_immutable')]
	private DateTimeImmutable $created;

	public function __construct(string $id, string $token)
	{
		$this->id = $id;
		$this->token = $token;
		$this->created = new DatePoint();
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getToken(): string
	{
		return $this->token;
	}

	public function setToken(string $token): static
	{
		$this->token = $token;

		return $this;
	}

	public function getCreated(): DateTimeImmutable
	{
		return $this->created;
	}

	public function setCreated(DateTimeImmutable $created): static
	{
		$this->created = $created;

		return $this;
	}

}
