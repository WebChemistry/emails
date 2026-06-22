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

	public function testResetCounter(): void
	{
		$this->inactivityPlugin->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'), 2);
		$this->inactivityPlugin->resetCounter($this->firstEmail, $this->sections->getSection('notifications'));

		$this->assertSame(0, $this->inactivityPlugin->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

}
