<?php declare(strict_types = 1);

namespace Tests\Plugin;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Component\Clock\MockClock;
use Tests\ConnectionInitializer;
use Tests\EmailManagerEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Plugin\LastSent\LastSentConfig;
use WebChemistry\Emails\Plugin\LastSent\LastSentPlugin;
use WebChemistry\Emails\Section\SectionBlueprint;
use WebChemistry\Emails\StringEmailRegistry;

final class LastSentPluginTest extends TestCase implements ConnectionInitializer
{

	use EmailManagerEnvironment;

	private MockClock $clock;

	private LastSentPlugin $plugin;

	public static function initializeConnection(Connection $connection): void
	{
		$connection->executeStatement('DROP TABLE IF EXISTS email_last_sent');
		$connection->executeStatement('CREATE TABLE email_last_sent (email VARCHAR(255), section VARCHAR(255), sent_at DATETIME, PRIMARY KEY(email, section))');
	}

	protected function setUp(): void
	{
		$this->clock = new MockClock(new DateTimeImmutable('2021-01-01 12:00:00'));
		$this->plugin = new LastSentPlugin($this->connectionAccessor, $this->clock);

		$this->dispatcher->addSubscriber($this->plugin);

		$this->sections->add(new SectionBlueprint('another', configs: [new LastSentConfig('4 hours')]));
	}

	public function configureSection(string $section): array
	{
		if ($section === 'notifications') {
			return [new LastSentConfig('8 hours')];
		}

		return [];
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

	public function testSaveRecordInForbiddenSection(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'section');

		$this->assertSame([], $this->databaseSnapshot());
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

		$this->plugin->cleanup($this->sections);

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

		$this->plugin->cleanup($this->sections);

		$this->assertSame([], $this->databaseSnapshot());
	}

	public function testCleanupTwoSections(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'another');

		$this->clock->sleep(3 * 60 * 60);

		$this->plugin->cleanup($this->sections);

		$this->assertCount(2, $this->databaseSnapshot());
	}

	public function testCleanupTwoSectionsAfterButOnlyOneMeetConditions(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'another');

		$this->clock->sleep(4 * 60 * 60);

		$this->plugin->cleanup($this->sections);

		$this->assertCount(1, $this->databaseSnapshot());
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
