<?php declare(strict_types = 1);

namespace Tests\Bridge\Symfony;

use Tests\TestCase;
use WebChemistry\Emails\Adapter\InMemoryAdapter;
use WebChemistry\Emails\Bridge\Symfony\DataCollector\EmailsDataCollector;
use WebChemistry\Emails\Bridge\Symfony\ProfilerMailerAdapter;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\HtmlMessage;
use WebChemistry\Emails\Mailer;
use WebChemistry\Emails\OperationType;
use WebChemistry\Emails\Section\SectionCategory;

final class EmailsDataCollectorTest extends TestCase
{

	public function testCollectsSentEmail(): void
	{
		$collector = new EmailsDataCollector();
		$adapter = new ProfilerMailerAdapter(new InMemoryAdapter(), $collector);
		$sender = new EmailAccount('sender@example.com', 'Sender');

		$adapter->send(
			[$this->firstEmailAccount, $this->secondEmailAccount],
			new HtmlMessage('<p>Hello</p>', 'HTML subject', $sender),
			[
				Mailer::SectionOption => 'newsletter',
				Mailer::CategoryOption => SectionCategory::Global,
			],
		);

		$collector->lateCollect();

		$this->assertSame(2, $collector->getSentCount());

		$sent = $collector->getSentEmails();
		$this->assertCount(1, $sent);
		$this->assertSame(['test@example.com', 'test2@example.com'], $sent[0]['recipients']);
		$this->assertSame('HTML subject', $sent[0]['subject']);
		$this->assertSame('Sender <sender@example.com>', $sent[0]['sender']);
		$this->assertSame('newsletter', $sent[0]['section']);
		$this->assertSame(SectionCategory::Global, $sent[0]['category']);
		$this->assertSame('<p>Hello</p>', $sent[0]['body']);
	}

	public function testDelegatesToInnerAdapter(): void
	{
		$collector = new EmailsDataCollector();
		$inner = new InMemoryAdapter();
		$adapter = new ProfilerMailerAdapter($inner, $collector);

		$adapter->send(
			[$this->firstEmailAccount],
			new HtmlMessage('<p>Hello</p>', 'Subject', new EmailAccount('sender@example.com')),
		);

		$this->assertCount(1, $inner->getSent());
	}

	public function testCollectsOperation(): void
	{
		$collector = new EmailsDataCollector();
		$adapter = new ProfilerMailerAdapter(new InMemoryAdapter(), $collector);

		$adapter->operate(
			[$this->firstEmailAccount],
			['group-a'],
			OperationType::Insert,
		);

		$collector->lateCollect();

		$operations = $collector->getOperations();
		$this->assertCount(1, $operations);
		$this->assertSame(['test@example.com'], $operations[0]['accounts']);
		$this->assertSame(['group-a'], $operations[0]['groups']);
		$this->assertSame(OperationType::Insert->name, $operations[0]['type']);
	}

	public function testDataIsExposedOnlyAfterLateCollect(): void
	{
		$collector = new EmailsDataCollector();
		$adapter = new ProfilerMailerAdapter(new InMemoryAdapter(), $collector);

		$adapter->send(
			[$this->firstEmailAccount],
			new HtmlMessage('<p>Hello</p>', 'Subject', new EmailAccount('sender@example.com')),
		);

		$this->assertSame([], $collector->getSentEmails());

		$collector->lateCollect();

		$this->assertCount(1, $collector->getSentEmails());
	}

	public function testResetClearsCollectedData(): void
	{
		$collector = new EmailsDataCollector();
		$adapter = new ProfilerMailerAdapter(new InMemoryAdapter(), $collector);

		$adapter->send(
			[$this->firstEmailAccount],
			new HtmlMessage('<p>Hello</p>', 'Subject', new EmailAccount('sender@example.com')),
		);

		$collector->lateCollect();
		$collector->reset();

		$this->assertSame(0, $collector->getSentCount());
		$this->assertSame([], $collector->getSentEmails());
		$this->assertSame([], $collector->getOperations());
	}

}
