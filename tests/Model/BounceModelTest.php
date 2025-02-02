<?php declare(strict_types = 1);

namespace Tests\Model;

use Tests\DatabaseEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Model\SoftBounceModel;

final class BounceModelTest extends TestCase
{

	use DatabaseEnvironment;

	private SoftBounceModel $model;

	protected function setUp(): void
	{
		$this->model = new SoftBounceModel($this->connectionAccessor);
	}

	public function testZeroBounce(): void
	{
		$this->assertSame(0, $this->model->getBounceCount($this->firstEmail));
	}

	public function testFirstBounce(): void
	{
		$suspended = $this->model->incrementBounce($this->firstEmail);

		$this->assertSame(1, $this->model->getBounceCount($this->firstEmail));
		$this->assertSame([], $suspended);
	}

	public function testSecondBounce(): void
	{
		$this->model->incrementBounce($this->firstEmail);
		$suspended = $this->model->incrementBounce($this->firstEmail);

		$this->assertSame(2, $this->model->getBounceCount($this->firstEmail));
		$this->assertSame([], $suspended);
	}

	public function testFinalBounce(): void
	{
		$this->model->incrementBounce($this->firstEmail);
		$this->model->incrementBounce($this->firstEmail);
		$suspended = $this->model->incrementBounce($this->firstEmail);

		$this->assertSame([$this->firstEmail], $suspended);
		$this->assertSame(0, $this->model->getBounceCount($this->firstEmail));
	}

}
