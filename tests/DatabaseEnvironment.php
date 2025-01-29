<?php declare(strict_types = 1);

namespace Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Persistence\ConnectionRegistry;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;

trait DatabaseEnvironment
{

	private static ConnectionRegistry $_registry;
	private static Connection $_connection;

	private Connection $connection;
	private ConnectionRegistry $registry;

	#[BeforeClass]
	public static function before(): void
	{
		self::$_connection = DriverManager::getConnection(TestHelper::getDatabaseConfiguration());

		self::$_connection->executeStatement(
			'CREATE TABLE IF NOT EXISTS email_bounce_counters (email VARCHAR(255) PRIMARY KEY, counter INT);' .
			'CREATE TABLE IF NOT EXISTS email_inactivity_counters (email VARCHAR(255), section VARCHAR(255), counter INT, PRIMARY KEY(email, section));' .
			'CREATE TABLE IF NOT EXISTS email_suspensions (email VARCHAR(255), type VARCHAR(255), section VARCHAR(255), created_at DATETIME, PRIMARY KEY(email, type, section));'
		);

		self::$_registry = new class(self::$_connection) implements ConnectionRegistry {

			public function __construct(
				private Connection $connection,
			)
			{
			}

			public function getDefaultConnectionName(): string
			{
				return 'default';
			}

			public function getConnection(?string $name = null): Connection
			{
				return $this->connection;
			}

			public function getConnections(): array
			{
				return [];
			}

			public function getConnectionNames(): array
			{
				return [];
			}

		};
	}

	#[Before(11)]
	public function setUpDatabase(): void
	{
		$this->connection = self::$_connection;
		$this->registry = self::$_registry;

		$this->connection->beginTransaction();
	}

	#[After]
	public function tearDownDatabase(): void
	{
		$this->connection->rollBack();
	}

	#[AfterClass]
	public static function after(): void
	{
		self::$_connection->close();
	}

}
