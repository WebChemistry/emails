<?php declare(strict_types = 1);

namespace Tests\Adapter;

use Tests\TestCase;
use WebChemistry\Emails\Adapter\CompoundAdapter;
use WebChemistry\Emails\Adapter\InMemoryAdapter;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\HtmlMessage;
use WebChemistry\Emails\OperationType;

final class CompoundAdapterTest extends TestCase
{

	public function testSendUsesTransactionalAdapter(): void
	{
		$transactional = new InMemoryAdapter();
		$marketing = new InMemoryAdapter();
		$adapter = new CompoundAdapter($transactional, $marketing);

		$adapter->send([$this->firstEmailAccount], $this->createMessage());

		$this->assertCount(1, $transactional->getSent());
		$this->assertSame($this->firstEmail, $transactional->getSent()[0]->getEmail());
		$this->assertSame([], $marketing->getSent());
	}

	public function testOperateUsesMarketingAdapter(): void
	{
		$transactional = new InMemoryAdapter();
		$marketing = new InMemoryAdapter();
		$adapter = new CompoundAdapter($transactional, $marketing);

		// InMemoryAdapter::operate() is a noop, so this only asserts routing does not touch send().
		$adapter->operate([$this->firstEmailAccount], [], OperationType::Insert);

		$this->assertSame([], $transactional->getSent());
		$this->assertSame([], $marketing->getSent());
	}

	private function createMessage(): HtmlMessage
	{
		return new HtmlMessage('<p>Content</p>', 'Subject', new EmailAccount('sender@example.com'));
	}

}
