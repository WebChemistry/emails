<?php declare(strict_types = 1);

namespace Tests\Plugin;

use Tests\TestCase;
use Tests\EmailManagerEnvironment;
use WebChemistry\Emails\StringEmailRegistry;

final class InactivityPluginTest extends TestCase
{

	use EmailManagerEnvironment;

	public function testNotSent(): void
	{
		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

	public function testFirstSent(): void
	{
		$inactive = $this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'), 2);

		$this->assertSame(1, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
		$this->assertSame([], $inactive);
	}

	public function testFinalSent(): void
	{
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'), 2);
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'), 2);
		$inactive = $this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'), 2);

		$this->assertSame([$this->firstEmail], $inactive);
		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

	public function testNotIncrementedForSectionWithoutConfig(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'section');

		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('section')));
	}

	public function testIncrementedThroughManager(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');

		$this->assertSame(1, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

	public function testResetOnOpen(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');
		$this->manager->emailOpened($this->firstEmail, 'notifications');

		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

	public function testResetOnSuspension(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');
		$this->manager->hardBounce($this->firstEmail);

		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

	public function testResetOnReset(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');
		$this->manager->reset($this->firstEmail);

		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

	public function testResetOnResubscribe(): void
	{
		$this->manager->afterEmailSent(new StringEmailRegistry([$this->firstEmail]), 'notifications');
		$this->manager->resubscribe($this->firstEmail, 'notifications');

		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

	public function testResetCounter(): void
	{
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'), 2);
		$this->inactivityPlugin->resetCounter($this->firstEmail, $this->sections->getSection('notifications'));

		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

	public function testIncrementIsScopedToSection(): void
	{
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'), 2);

		$this->assertSame(1, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('section')));
	}

	public function testSuspensionResetsEverySection(): void
	{
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'), 2);
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('section'), 2);

		$this->manager->hardBounce($this->firstEmail);

		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('section')));
	}

	public function testResetResetsEverySection(): void
	{
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'), 2);
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('section'), 2);

		$this->manager->reset($this->firstEmail);

		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('section')));
	}

	public function testResubscribeResetsOnlyItsSection(): void
	{
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'), 2);
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('section'), 2);

		$this->manager->resubscribe($this->firstEmail, 'notifications');

		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
		$this->assertSame(1, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('section')));
	}

	public function testOpenResetsOnlyItsSection(): void
	{
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'), 2);
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('section'), 2);

		$this->manager->emailOpened($this->firstEmail, 'notifications');

		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
		$this->assertSame(1, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('section')));
	}

	public function testMultipleEmails(): void
	{
		$this->inactivityPlugin->incrementCounter([$this->firstEmail, $this->secondEmail], $this->sections->getSection('notifications'), 2);

		$this->assertSame(1, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
		$this->assertSame(1, $this->inactivityPlugin->getCount($this->secondEmail, $this->sections->getSection('notifications')));
	}

	public function testMultipleEmailsFinalSent(): void
	{
		$emails = [$this->firstEmail, $this->secondEmail];

		$this->inactivityPlugin->incrementCounter($emails, $this->sections->getSection('notifications'), 2);
		$this->inactivityPlugin->incrementCounter($emails, $this->sections->getSection('notifications'), 2);
		$inactive = $this->inactivityPlugin->incrementCounter($emails, $this->sections->getSection('notifications'), 2);

		$this->assertSame($emails, $inactive);
	}

}
