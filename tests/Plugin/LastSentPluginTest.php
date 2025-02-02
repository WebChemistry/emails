<?php declare(strict_types = 1);

namespace Tests\Plugin;

use DateTimeImmutable;
use Symfony\Component\Clock\MockClock;
use Tests\EmailManagerEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Plugin\LastSent\LastSentPlugin;
use WebChemistry\Emails\StringEmailRegistry;

final class LastSentPluginTest extends TestCase
{

	use EmailManagerEnvironment;

	private MockClock $clock;

	private LastSentPlugin $plugin;

	protected function setUp(): void
	{
		$this->clock = new MockClock(new DateTimeImmutable('2021-01-01 12:00:00'));
		$this->plugin = new LastSentPlugin($this->connectionAccessor,'8 hours', $this->clock);

		$this->connection->executeStatement('CREATE TABLE IF NOT EXISTS email_last_sent (email VARCHAR(255) PRIMARY KEY, sent_at DATETIME)');

		$this->dispatcher->addSubscriber($this->plugin);
	}

	public function testNoRecord(): void
	{
		$this->manager->beforeEmailSent($registry = new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->assertCount(1, $registry->getEmails());
	}

	public function testSaveRecord(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->assertSame([
			[
				'email' => $this->firstEmail,
				'sent_at' => '2021-01-01 12:00:00',
			],
		], $this->databaseSnapshot());
	}

	public function testSentInForbiddenTime(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->clock->sleep((8 * 60 * 60) - 1);

		$this->manager->beforeEmailSent($registry = new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->assertCount(0, $registry->getEmails());
	}

	public function testSentAfterForbiddenTime(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->clock->sleep(8 * 60 * 60);

		$this->manager->beforeEmailSent($registry = new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->assertCount(1, $registry->getEmails());
	}

	public function testCleanupInForbiddenTime(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->clock->sleep((8 * 60 * 60) - 1);

		$this->assertSame(0, $this->plugin->cleanup());

		$this->assertSame([
			[
				'email' => $this->firstEmail,
				'sent_at' => '2021-01-01 12:00:00',
			],
		], $this->databaseSnapshot());
	}

	public function testCleanupAfterForbiddenTime(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->clock->sleep(8 * 60 * 60);

		$this->assertSame(1, $this->plugin->cleanup());

		$this->assertSame([], $this->databaseSnapshot());
	}

	/**
	 * @return array{ email: string, sent_at: string }[]
	 */
	private function databaseSnapshot(): array
	{
		return $this->connection->createQueryBuilder()
			->select('email, sent_at')
			->from('email_last_sent')
			->orderBy('sent_at', 'ASC')
			->executeQuery()->fetchAllAssociative();
	}

}
