<?php declare(strict_types = 1);

namespace Tests;

use Doctrine\DBAL\Tools\DsnParser;

final class TestHelper
{

	/**
	 * @return mixed[]
	 */
	public static function getDatabaseConfiguration(): array
	{
		if (is_string(getenv('DB_DSN'))) {
			$dsn = getenv('DB_DSN');
		} else {
			$dsn = 'pdo-sqlite:///:memory:';
		}

		return (new DsnParser())->parse($dsn);
	}

}
