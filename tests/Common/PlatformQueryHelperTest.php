<?php declare(strict_types = 1);

namespace Tests\Common;

use Tests\TestCase;
use WebChemistry\Emails\Common\PlatformQueryHelper;
use WebChemistry\Emails\Exception\UnsupportedPlatformException;

final class PlatformQueryHelperTest extends TestCase
{

	public function testMysqlColumns(): void
	{
		$resolve = PlatformQueryHelper::updateColumns(['name', 'email']);

		$this->assertSame('name = VALUES(name), email = VALUES(email)', $resolve('mysql'));
	}

	public function testSqliteColumns(): void
	{
		$resolve = PlatformQueryHelper::updateColumns(['name', 'email']);

		$this->assertSame('name = excluded.name, email = excluded.email', $resolve('sqlite'));
	}

	public function testUnsupportedPlatformThrows(): void
	{
		$resolve = PlatformQueryHelper::updateColumns(['name']);

		$this->expectException(UnsupportedPlatformException::class);

		$resolve('postgres');
	}

}
