<?php declare(strict_types = 1);

namespace Tests;

use Doctrine\DBAL\Connection;

interface ConnectionInitializer
{

	public static function initializeConnection(Connection $connection): void;

}
