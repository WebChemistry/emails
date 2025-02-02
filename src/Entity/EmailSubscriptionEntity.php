<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use WebChemistry\Emails\Section\Section;
use WebChemistry\Emails\Type\SuspensionType;
use WebChemistry\Emails\Type\UnsubscribeType;

trait EmailSubscriptionEntity // @phpstan-ignore trait.unused
{

	public const TableName = 'email_subscriptions';

	#[Id]
	#[Column(type: 'string', length: 255)]
	private string $email;

	#[Id]
	#[Column(type: 'string', length: Section::MaxLength)]
	private string $section;

	#[Id]
	#[Column(type: 'string', length: Section::MaxLength)]
	private string $category;

	#[Id]
	#[Column(type: 'string', length: 10, enumType: SuspensionType::class)]
	private UnsubscribeType $type;

	#[Column(type: 'datetime_immutable')]
	private DateTimeImmutable $createdAt;

	public function __construct(string $email, UnsubscribeType $type)
	{
		$this->email = $email;
		$this->type = $type;
		$this->createdAt = new DateTimeImmutable();
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getType(): UnsubscribeType
	{
		return $this->type;
	}

	public function getCreatedAt(): DateTimeImmutable
	{
		return $this->createdAt;
	}

}
