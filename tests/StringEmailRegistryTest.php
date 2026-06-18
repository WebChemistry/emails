<?php declare(strict_types = 1);

namespace Tests;

use WebChemistry\Emails\StringEmailRegistry;

final class StringEmailRegistryTest extends TestCase
{

	public function testIsEmpty(): void
	{
		$this->assertTrue((new StringEmailRegistry([]))->isEmpty());
		$this->assertFalse((new StringEmailRegistry([$this->firstEmail]))->isEmpty());
	}

	public function testGetEmails(): void
	{
		$registry = new StringEmailRegistry([$this->firstEmail, $this->secondEmail]);

		$this->assertSame([$this->firstEmail, $this->secondEmail], $registry->getEmails());
	}

	public function testRemoveExistingEmail(): void
	{
		$registry = new StringEmailRegistry([$this->firstEmail, $this->secondEmail]);

		$registry->remove($this->firstEmail);

		$this->assertSame([1 => $this->secondEmail], $registry->getEmails());
		$this->assertSame([$this->firstEmail], $registry->getRemoved());
	}

	public function testRemoveUnknownEmailIsNoop(): void
	{
		$registry = new StringEmailRegistry([$this->firstEmail]);

		$registry->remove($this->secondEmail);

		$this->assertSame([$this->firstEmail], $registry->getEmails());
		$this->assertSame([], $registry->getRemoved());
	}

	public function testRemovedIsEmptyByDefault(): void
	{
		$this->assertSame([], (new StringEmailRegistry([$this->firstEmail]))->getRemoved());
	}

}
