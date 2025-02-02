<?php declare(strict_types = 1);

namespace Tests\Model;

use Tests\DatabaseEnvironment;
use Tests\SectionEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Model\InactivityModel;

final class InactivityModelTest extends TestCase
{

	use DatabaseEnvironment;
	use SectionEnvironment;

	private InactivityModel $model;

	protected function setUp(): void
	{
		$this->model = new InactivityModel(2, $this->registry);
	}

	public function testNotSent(): void
	{
		$this->assertSame(0, $this->model->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

	public function testFirstSent(): void
	{
		$inactive = $this->model->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'));

		$this->assertSame(1, $this->model->getCount($this->firstEmail, $this->sections->getSection('notifications')));
		$this->assertSame([], $inactive);
	}

	public function testSecondSent(): void
	{
		$this->model->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'));
		$inactive = $this->model->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'));

		$this->assertSame(2, $this->model->getCount($this->firstEmail, $this->sections->getSection('notifications')));
		$this->assertSame([], $inactive);
	}

	public function testFinalSent(): void
	{
		$this->model->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'));
		$this->model->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'));
		$inactive = $this->model->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'));

		$this->assertSame([$this->firstEmail], $inactive);
		$this->assertSame(0, $this->model->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

	public function testSecondSentDifferentSection(): void
	{
		$this->model->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'));
		$this->model->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'));
		$this->model->incrementCounter($this->firstEmail, $this->sections->getSection('section'));

		$this->assertSame(2, $this->model->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

	public function testMultipleEmails(): void
	{
		$this->model->incrementCounter([$this->firstEmail, $this->secondEmail], $this->sections->getSection('notifications'));

		$this->assertSame(1, $this->model->getCount($this->firstEmail, $this->sections->getSection('notifications')));
		$this->assertSame(1, $this->model->getCount($this->secondEmail, $this->sections->getSection('notifications')));
	}

	public function testMultipleEmailsFinalSent(): void
	{
		$this->model->incrementCounter([$this->firstEmail, $this->secondEmail], $this->sections->getSection('notifications'));
		$this->model->incrementCounter([$this->firstEmail, $this->secondEmail], $this->sections->getSection('notifications'));
		$inactive = $this->model->incrementCounter([$this->firstEmail, $this->secondEmail], $this->sections->getSection('notifications'));

		$this->assertSame([$this->firstEmail, $this->secondEmail], $inactive);
	}

	public function testResetCounter(): void
	{
		$this->model->incrementCounter($this->firstEmail, $this->sections->getSection('notifications'));
		$this->model->resetCounter($this->firstEmail, $this->sections->getSection('notifications'));

		$this->assertSame(0, $this->model->getCount($this->firstEmail, $this->sections->getSection('notifications')));
	}

}
