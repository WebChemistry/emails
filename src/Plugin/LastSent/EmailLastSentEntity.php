<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Plugin\LastSent;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use WebChemistry\Emails\Section\Section;

trait EmailLastSentEntity // @phpstan-ignore trait.unused
{

	public const TableName = 'email_last_sent';

	#[Id]
	#[Column(type: 'string', length: 255)]
	private string $email;

	#[Id]
	#[Column(type: 'string', length: Section::MaxLength)]
	private string $section;

	#[Column(type: 'datetime_immutable')]
	private DateTimeImmutable $sentAt;

	public function __construct(string $email)
	{
		$this->email = $email;
		$this->sentAt = new DateTimeImmutable();
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getSentAt(): DateTimeImmutable
	{
		return $this->sentAt;
	}

}
