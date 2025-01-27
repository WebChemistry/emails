<?php declare(strict_types = 1);

namespace Tests\Bounce;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\Persistence\ConnectionRegistry;
use PHPUnit\Framework\TestCase;
use WebChemistry\Emails\Bounce\BounceManager;
use WebChemistry\Emails\Subscription\EmailUnsubscriber;

final class BounceManagerTest extends TestCase
{

	private EmailUnsubscriber $unsubscriber;

	private BounceManager $manager;

	protected function setUp(): void
	{
		$parser = new DsnParser();

		$connection = DriverManager::getConnection($parser->parse('pdo-sqlite:///:memory:'));
		$registry = new class($connection) implements ConnectionRegistry {

			public function __construct(
				private Connection $connection,
			)
			{
			}

			public function getDefaultConnectionName(): string
			{
				return 'default';
			}

			public function getConnection(?string $name = null): object
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

		$this->unsubscriber = new class implements EmailUnsubscriber {

			/** @var string[] */
			public array $emails = [];

			public function unsubscribe(string $email): void
			{
				$this->emails[] = $email;
			}

		};
		$this->manager = new BounceManager($registry, $this->unsubscriber);

		$connection->executeStatement('CREATE TABLE email_bounce_counters (email VARCHAR PRIMARY KEY, bounce_count INTEGER)');
	}

	public function testZeroBounce(): void
	{
		$this->assertSame(0, $this->manager->getBounceCount('test@example.com'));
	}

	public function testFirstBounce(): void
	{
		$this->manager->incrementBounce('test@example.com');

		$this->assertSame(1, $this->manager->getBounceCount('test@example.com'));
		$this->assertEmpty($this->unsubscriber->emails);
	}

	public function testSecondBounce(): void
	{
		$this->manager->incrementBounce('test@example.com');
		$this->manager->incrementBounce('test@example.com');

		$this->assertSame(2, $this->manager->getBounceCount('test@example.com'));
		$this->assertEmpty($this->unsubscriber->emails);
	}

	public function testThirdBounce(): void
	{
		$this->manager->incrementBounce('test@example.com');
		$this->manager->incrementBounce('test@example.com');
		$this->manager->incrementBounce('test@example.com');

		$this->assertSame(['test@example.com'], $this->unsubscriber->emails);
		$this->assertSame(0, $this->manager->getBounceCount('test@example.com'));
	}

}
