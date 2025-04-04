<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Id;
use WebChemistry\Emails\Section\Section;

trait EmailInactivityCounterEntity // @phpstan-ignore trait.unused
{

	public const string TableName = 'email_inactivity_counters';

	#[Id]
	#[Column(type: 'string', length: 255)]
	private string $email;

	#[Id]
	#[Column(type: 'string', length: Section::MaxLength)]
	private string $section;

	#[Column(type: 'integer', options: ['default' => '0'])]
	private int $counter = 0;

	public function __construct(string $email, string $section)
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
