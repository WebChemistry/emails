<?php declare(strict_types = 1);

namespace Tests\Adapter;

use InvalidArgumentException;
use Tests\TestCase;
use WebChemistry\Emails\Adapter\InMemoryAdapter;
use WebChemistry\Emails\Adapter\SelectiveAdapter;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\HtmlMessage;

final class SelectiveAdapterTest extends TestCase
{

	public function testMissingDefaultMailerThrows(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Default mailer not found.');

		new SelectiveAdapter(['newsletter' => new InMemoryAdapter()]);
	}

	public function testSendWithoutSectionUsesDefaultMailer(): void
	{
		$default = new InMemoryAdapter();
		$newsletter = new InMemoryAdapter();
		$adapter = new SelectiveAdapter(['default' => $default, 'newsletter' => $newsletter]);

		$adapter->send([$this->firstEmailAccount], $this->createMessage());

		$this->assertCount(1, $default->getSent());
		$this->assertSame([], $newsletter->getSent());
	}

	public function testSendWithSectionUsesMatchingMailer(): void
	{
		$default = new InMemoryAdapter();
		$newsletter = new InMemoryAdapter();
		$adapter = new SelectiveAdapter(['default' => $default, 'newsletter' => $newsletter]);

		$adapter->send([$this->firstEmailAccount], $this->createMessage(), [SelectiveAdapter::OptionKey => 'newsletter']);

		$this->assertCount(1, $newsletter->getSent());
		$this->assertSame([], $default->getSent());
	}

	public function testSendWithUnknownSectionThrows(): void
	{
		$adapter = new SelectiveAdapter(['default' => new InMemoryAdapter()]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Mailer unknown not found, possible mailers: default');

		$adapter->send([$this->firstEmailAccount], $this->createMessage(), [SelectiveAdapter::OptionKey => 'unknown']);
	}

	private function createMessage(): HtmlMessage
	{
		return new HtmlMessage('<p>Content</p>', 'Subject', new EmailAccount('sender@example.com'));
	}

}
