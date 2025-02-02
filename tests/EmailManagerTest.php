<?php declare(strict_types = 1);

namespace Tests;

use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Event\InactiveEmailsEvent;
use WebChemistry\Emails\Section\Section;
use WebChemistry\Emails\Section\SectionCategory;
use WebChemistry\Emails\StringEmailRegistry;

final class EmailManagerTest extends TestCase
{

	use EmailManagerEnvironment;

	public function testSuccessfulUnsubscribe(): void
	{
		$link = $this->manager->addUnsubscribeQueryParameter('http://example.com', $this->firstEmail, 'notifications');

		$this->manager->processSubscribeUnsubscribeQueryParameter($link);

		$this->sendingIsForbidden($this->firstEmail, 'notifications');
	}

	public function testSuccessfulResubscribe(): void
	{
		$this->manager->unsubscribe($this->firstEmail, 'notifications');
		$link = $this->manager->addResubscribeQueryParameter('http://example.com', $this->firstEmail, 'notifications');

		$this->manager->processSubscribeUnsubscribeQueryParameter($link);

		$this->sendingIsAllowed($this->firstEmail, 'notifications');
	}

	public function testUnsuccessfulUnsubscribe(): void
	{
		$link = $this->manager->addUnsubscribeQueryParameter('http://example.com', $this->firstEmail, 'notifications');

		$this->manager->processSubscribeUnsubscribeQueryParameter(substr($link, 0, -1));

		$this->sendingIsAllowed($this->firstEmail, 'notifications');
	}

	public function testUnsubscribeBecauseOfInactivity(): void
	{
		$called = false;
		$this->dispatcher->addListener(InactiveEmailsEvent::class, function () use (&$called): void {
			$called = true;
		});

		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->sendingIsForbidden($this->firstEmail, 'notifications');
		$this->assertTrue($called);
	}

	public function testInactive(): void
	{
		$this->manager->inactive($this->firstEmail, 'notifications');
		$this->manager->inactive($this->firstEmail, 'notifications');

		$this->sendingIsForbidden($this->firstEmail, 'notifications');
		$this->sendingIsAllowed($this->firstEmail, Section::Essential);
	}

	public function testSpamComplaint(): void
	{
		$this->manager->spamComplaint($this->firstEmail);

		$this->sendingIsForbidden($this->firstEmail, 'notifications');
		$this->sendingIsAllowed($this->firstEmail, Section::Essential);
	}

	public function testSoftBounce(): void
	{
		$this->manager->softBounce($this->firstEmail);
		$this->manager->softBounce($this->firstEmail);
		$this->manager->softBounce($this->firstEmail);

		$this->sendingIsForbidden($this->firstEmail, 'notifications');
		$this->sendingIsAllowed($this->firstEmail, Section::Essential);
	}

	public function testHardBounce(): void
	{
		$this->manager->hardBounce($this->firstEmail);

		$this->sendingIsForbidden($this->firstEmail, 'notifications');
		$this->sendingIsForbidden($this->firstEmail, Section::Essential);
	}

	public function testEmailOpened(): void
	{
		$this->simulateSent($this->firstEmail, 'notifications');

		$this->assertSame(1, $this->inactivityModel->getCount($this->firstEmail, $this->sections->getSection('notifications')));

		$this->manager->emailOpened($this->firstEmail, 'notifications');

		$this->assertSame(0, $this->inactivityModel->getCount($this->firstEmail, $this->sections->getSection('notifications')));
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

		$this->sendingIsForbidden($this->firstEmail, 'notifications');
		$this->sendingIsAllowed($this->firstEmail, Section::Essential);
	}

	public function testReset(): void
	{
		$this->manager->unsubscribe($this->firstEmail, 'notifications');

		$this->manager->softBounce($this->firstEmail);
		$this->manager->hardBounce($this->firstEmail);

		$this->manager->reset($this->firstEmail);

		$this->sendingIsAllowed($this->firstEmail, 'notifications');
		$this->assertSame(0, $this->softBounceModel->getBounceCount($this->firstEmail));
	}

	private function simulateSent(array|string $emails, string $section, string $category = SectionCategory::Global): void
	{
		$emails = is_string($emails) ? [$emails] : $emails;

		$this->manager->beforeEmailSent($registry = new StringEmailRegistry($emails), $section, $category);
		$this->manager->afterEmailSent($registry, $section, $category);
	}

	private function sendingIsAllowed(string $email, string $section, string $category = SectionCategory::Global): void
	{
		$this->assertTrue($this->manager->canSend($email, $section, $category));

		$this->manager->beforeEmailSent($registry = new StringEmailRegistry([$email]), $section, $category);

		$this->assertCount(1, $registry->getEmails());
	}

	private function sendingIsForbidden(string $email, string $section, string $category = SectionCategory::Global): void
	{
		$this->assertFalse($this->manager->canSend($email, $section, $category));

		$this->manager->beforeEmailSent($registry = new StringEmailRegistry([$email]), $section, $category);

		$this->assertCount(0, $registry->getEmails());
	}

}
