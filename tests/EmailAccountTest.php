<?php declare(strict_types = 1);

namespace Tests;

use WebChemistry\Emails\EmailAccount;

final class EmailAccountTest extends TestCase
{

	public function testToStringWithoutName(): void
	{
		$account = new EmailAccount('test@example.com');

		$this->assertSame('test@example.com', $account->toString());
	}

	public function testToStringWithEmptyName(): void
	{
		$account = new EmailAccount('test@example.com', '');

		$this->assertSame('test@example.com', $account->toString());
	}

	public function testToStringWithName(): void
	{
		$account = new EmailAccount('test@example.com', 'John Doe');

		$this->assertSame('John Doe <test@example.com>', $account->toString());
	}

}
