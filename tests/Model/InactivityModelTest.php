<?php declare(strict_types = 1);

namespace Tests\Model;

use Tests\DatabaseEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SubscriberModel;

final class InactivityModelTest extends TestCase
{

	use DatabaseEnvironment;

	private InactivityModel $model;

	private SubscriberModel $subscriberModel;

	protected function setUp(): void
	{
		$this->subscriberModel = new SubscriberModel($this->registry);
		$this->model = new InactivityModel(2, $this->registry, $this->subscriberModel);
	}

	public function testNotSent(): void
	{
		$this->assertSame(0, $this->model->getCount($this->firstEmail));
	}

	public function testFirstSent(): void
	{
		$this->model->incrementCounter($this->firstEmail);

		$this->assertSame(1, $this->model->getCount($this->firstEmail));
	}

	public function testSecondSent(): void
	{
		$this->model->incrementCounter($this->firstEmail);
		$this->model->incrementCounter($this->firstEmail);

		$this->assertSame(2, $this->model->getCount($this->firstEmail));
	}

	public function testFinalSent(): void
	{
		$this->model->incrementCounter($this->firstEmail);
		$this->model->incrementCounter($this->firstEmail);
		$this->model->incrementCounter($this->firstEmail);

		$this->assertSame(0, $this->model->getCount($this->firstEmail));
		$this->assertTrue($this->subscriberModel->isSuspended($this->firstEmail));
		$this->assertSame(['inactivity'], $this->subscriberModel->getReasons($this->firstEmail));
	}

	public function testFinalSentDifferentSection(): void
	{
		$this->model->incrementCounter($this->firstEmail, 'section');
		$this->model->incrementCounter($this->firstEmail, 'section');
		$this->model->incrementCounter($this->firstEmail, 'section');

		$this->assertSame(0, $this->model->getCount($this->firstEmail, 'section'));
		$this->assertFalse($this->subscriberModel->isSuspended($this->firstEmail));
		$this->assertTrue($this->subscriberModel->isSuspended($this->firstEmail, 'section'));
		$this->assertSame([], $this->subscriberModel->getReasons($this->firstEmail));
		$this->assertSame(['inactivity'], $this->subscriberModel->getReasons($this->firstEmail, 'section'));
	}

	public function testSecondSentDifferentSection(): void
	{
		$this->model->incrementCounter($this->firstEmail);
		$this->model->incrementCounter($this->firstEmail);
		$this->model->incrementCounter($this->firstEmail, 'section');

		$this->assertSame(2, $this->model->getCount($this->firstEmail));
	}

	public function testMultipleSent(): void
	{
		$this->model->incrementCounter([$this->firstEmail, $this->secondEmail]);

		$this->assertSame(1, $this->model->getCount($this->firstEmail));
		$this->assertSame(1, $this->model->getCount($this->secondEmail));
	}

	public function testResetCounter(): void
	{
		$this->model->incrementCounter($this->firstEmail);
		$this->model->resetCounter($this->firstEmail);

		$this->assertSame(0, $this->model->getCount($this->firstEmail));
	}

}
