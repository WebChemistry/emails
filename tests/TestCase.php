<?php declare(strict_types = 1);

namespace Tests;

use PHPUnit\Framework\Attributes\Before;
use WebChemistry\Emails\EmailAccount;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{

	protected string $firstEmail = 'test@example.com';
	protected string $secondEmail = 'test2@example.com';
	protected string $thirdEmail = 'test3@example.com';

	protected EmailAccount $firstEmailAccount;
	protected EmailAccount $secondEmailAccount;
	protected EmailAccount $thirdEmailAccount;

	#[Before]
	protected function setUpAccounts(): void
	{
		$this->firstEmailAccount = new EmailAccount($this->firstEmail);
		$this->secondEmailAccount = new EmailAccount($this->secondEmail);
		$this->thirdEmailAccount = new EmailAccount($this->thirdEmail);
	}

}
