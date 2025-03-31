<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use WebChemistry\Emails\Type\SuspensionType;

trait EmailSuspensionEntity // @phpstan-ignore trait.unused
{

	public const string TableName = 'email_suspensions';

	#[Id]
	#[Column(type: 'string', length: 255)]
	private string $email;

	#[Id]
	#[Column(type: 'string', length: 14, enumType: SuspensionType::class)]
	private SuspensionType $type;

	#[Column(type: 'datetime_immutable')]
	private DateTimeImmutable $createdAt;

	public function __construct(string $email, SuspensionType $type)
	{
		$this->email = $email;
		$this->type = $type;
		$this->createdAt = new DateTimeImmutable();
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getType(): SuspensionType
	{
		return $this->type;
	}

	public function getCreatedAt(): DateTimeImmutable
	{
		return $this->createdAt;
	}

}
