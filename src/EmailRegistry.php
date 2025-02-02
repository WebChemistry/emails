<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

interface EmailRegistry
{

	public function isEmpty(): bool;

	/**
	 * @return string[]
	 */
	public function getEmails(): array;

	public function remove(string $email): void;

	/**
	 * @return string[]
	 */
	public function getRemoved(): array;

}
