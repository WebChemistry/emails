<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use WebChemistry\Emails\EmailManager;

trait EmailInactivityCounterEntity // @phpstan-ignore trait.unused
{

	public const TableName = 'email_inactivity_counters';

	#[Id]
	#[Column(type: 'string', length: 255)]
	private string $email;

	#[Id]
	#[Column(type: 'string', length: 15)]
	private string $section;

	#[Column(type: 'integer', options: ['default' => '0'])]
	private int $counter = 0;

	public function __construct(string $email, string $section = EmailManager::SectionGlobal)
	{
		$this->email = $email;
		$this->section = $section;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function getCounter(): int
	{
		return $this->counter;
	}

	public function getSection(): string
	{
		return $this->section;
	}

	public function increment(): void
	{
		$this->counter++;
	}

}
