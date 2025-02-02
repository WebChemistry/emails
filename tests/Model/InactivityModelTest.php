<?php declare(strict_types = 1);

namespace Tests\Model;

use Tests\DatabaseEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Section\SectionConfig;
use WebChemistry\Emails\Section\Sections;

final class InactivityModelTest extends TestCase
{

	use DatabaseEnvironment;

	private InactivityModel $model;

	protected function setUp(): void
	{
		$sections = new Sections();
		$sections->addSection(new SectionConfig('notifications', ['article', 'mention']));
		$sections->addSection(new SectionConfig('diff'));
		$this->model = new InactivityModel(2, $this->registry, $sections);
	}

	public function testNotSent(): void
	{
		$this->assertSame(0, $this->model->getCount($this->firstEmail, 'notifications'));
	}

	public function testFirstSent(): void
	{
		$inactive = $this->model->incrementCounter($this->firstEmail, 'notifications');

		$this->assertSame(1, $this->model->getCount($this->firstEmail, 'notifications'));
		$this->assertSame([], $inactive);
	}

	public function testSecondSent(): void
	{
		$this->model->incrementCounter($this->firstEmail, 'notifications');
		$inactive = $this->model->incrementCounter($this->firstEmail, 'notifications');

		$this->assertSame(2, $this->model->getCount($this->firstEmail, 'notifications'));
		$this->assertSame([], $inactive);
	}

	public function testFinalSent(): void
	{
		$this->model->incrementCounter($this->firstEmail, 'notifications');
		$this->model->incrementCounter($this->firstEmail, 'notifications');
		$inactive = $this->model->incrementCounter($this->firstEmail, 'notifications');

		$this->assertSame([$this->firstEmail], $inactive);
		$this->assertSame(0, $this->model->getCount($this->firstEmail, 'notifications'));
	}

	public function testSecondSentDifferentSection(): void
	{
		$this->model->incrementCounter($this->firstEmail, 'notifications');
		$this->model->incrementCounter($this->firstEmail, 'notifications');
		$this->model->incrementCounter($this->firstEmail, 'diff');

		$this->assertSame(2, $this->model->getCount($this->firstEmail, 'notifications'));
	}

	public function testMultipleEmails(): void
	{
		$this->model->incrementCounter([$this->firstEmail, $this->secondEmail], 'notifications');

		$this->assertSame(1, $this->model->getCount($this->firstEmail, 'notifications'));
		$this->assertSame(1, $this->model->getCount($this->secondEmail, 'notifications'));
	}

	public function testMultipleEmailsFinalSent(): void
	{
		$this->model->incrementCounter([$this->firstEmail, $this->secondEmail], 'notifications');
		$this->model->incrementCounter([$this->firstEmail, $this->secondEmail], 'notifications');
		$inactive = $this->model->incrementCounter([$this->firstEmail, $this->secondEmail], 'notifications');

		$this->assertSame([$this->firstEmail, $this->secondEmail], $inactive);
	}

	public function testResetCounter(): void
	{
		$this->model->incrementCounter($this->firstEmail, 'notifications');
		$this->model->resetCounter($this->firstEmail, 'notifications');

		$this->assertSame(0, $this->model->getCount($this->firstEmail, 'notifications'));
	}

}
