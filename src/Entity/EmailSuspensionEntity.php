<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use WebChemistry\Emails\EmailManager;

trait EmailSuspensionEntity // @phpstan-ignore trait.unused
{

	public const TableName = 'email_suspensions';

	#[Id]
	#[Column(type: 'string', length: 255)]
	private string $email;

	#[Id]
	#[Column(type: 'string', length: 14)]
	private string $type;

	#[Id]
	#[Column(type: 'string', length: 15)]
	private string $section;

	public function __construct(string $email, string $type, string $section = EmailManager::SectionGlobal)
	{
		$this->email = $email;
		$this->type = $type;
		$this->section = $section;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getSection(): string
	{
		return $this->section;
	}

}
