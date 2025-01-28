<?php declare(strict_types = 1);

namespace Tests;

use PHPUnit\Event\TestRunner\Started;
use PHPUnit\Event\TestRunner\StartedSubscriber;

final class PhpUnitSubscriber implements StartedSubscriber
{

	public function notify(Started $event): void
	{
		$driver = TestHelper::getDatabaseConfiguration()['driver'] ?? 'unknown';

		echo "Driver: $driver\n\n";
	}

}
