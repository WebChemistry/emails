<?php declare(strict_types = 1);

namespace Tests;

use WebChemistry\Emails\Event\InactiveEmailsEvent;
use WebChemistry\Emails\StringEmailRegistry;

final class EmailManagerTest extends TestCase
{

	use EmailManagerEnvironment;

	public function testSuccessfulUnsubscribe(): void
	{
		$link = $this->manager->addUnsubscribeQueryParameter('http://example.com', $this->firstEmail, 'notifications');

		$this->manager->processSubscribeUnsubscribeQueryParameter($link);

		$this->assertFalse($this->manager->canSend($this->firstEmail, 'notifications'));
	}

	public function testSuccessfulResubscribe(): void
	{
		$this->manager->unsubscribe($this->firstEmail, 'notifications');
		$link = $this->manager->addResubscribeQueryParameter('http://example.com', $this->firstEmail, 'notifications');

		$this->manager->processSubscribeUnsubscribeQueryParameter($link);

		$this->assertTrue($this->manager->canSend($this->firstEmail, 'notifications'));
	}

	public function testUnsuccessfulUnsubscribe(): void
	{
		$link = $this->manager->addUnsubscribeQueryParameter('http://example.com', $this->firstEmail, 'notifications');

		$this->manager->processSubscribeUnsubscribeQueryParameter(substr($link, 0, -1));

		$this->assertTrue($this->manager->canSend($this->firstEmail, 'notifications'));
	}

	public function testUnsubscribeBecauseOfInactivity(): void
	{
		$called = false;
		$this->dispatcher->addListener(InactiveEmailsEvent::class, function () use (&$called): void {
			$called = true;
		});

		$this->manager->beforeEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');
		$this->manager->beforeEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');
		$this->manager->beforeEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->assertFalse($this->manager->canSend($this->firstEmail, 'notifications'));
		$this->assertTrue($called);
	}

	public function testFilterFromSuspension(): void
	{
		$this->manager->hardBounce($this->firstEmail);

		$this->manager->beforeEmailSent($registry = new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->assertCount(0, $registry->getEmails());
	}

	public function testFilterFromUnsubscribe(): void
	{
		$this->manager->unsubscribe($this->firstEmail, 'notifications');

		$this->manager->beforeEmailSent($registry = new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->assertCount(0, $registry->getEmails());
	}

	public function testReset(): void
	{
		$this->manager->unsubscribe($this->firstEmail, 'notifications');

		$this->manager->softBounce($this->firstEmail);
		$this->manager->hardBounce($this->firstEmail);

		$this->manager->reset($this->firstEmail);

		$this->assertTrue($this->manager->canSend($this->firstEmail, 'notifications'));
		$this->assertSame(0, $this->softBounceModel->getBounceCount($this->firstEmail));
	}

}
