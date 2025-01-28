<?php declare(strict_types = 1);

namespace Tests\Model;

use Tests\DatabaseEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriberModel;

final class BounceModelTest extends TestCase
{

	use DatabaseEnvironment;

	public const TableSql = 'CREATE TABLE IF NOT EXISTS email_bounce_counters (email VARCHAR PRIMARY KEY, bounce_count INTEGER);';

	private SoftBounceModel $model;

	private SubscriberModel $subscriberModel;

	private static function getInitialSql(): string
	{
		return self::TableSql . SubscriberModelTest::TableSql;
	}

	protected function setUp(): void
	{
		$this->subscriberModel = new SubscriberModel($this->registry);
		$this->model = new SoftBounceModel($this->registry, $this->subscriberModel);
	}

	public function testZeroBounce(): void
	{
		$this->assertSame(0, $this->model->getBounceCount($this->firstEmail));
	}

	public function testFirstBounce(): void
	{
		$this->model->incrementBounce($this->firstEmail);

		$this->assertSame(1, $this->model->getBounceCount($this->firstEmail));
		$this->assertFalse($this->subscriberModel->isSuspended($this->firstEmail));
	}

	public function testSecondBounce(): void
	{
		$this->model->incrementBounce($this->firstEmail);
		$this->model->incrementBounce($this->firstEmail);

		$this->assertSame(2, $this->model->getBounceCount($this->firstEmail));
		$this->assertFalse($this->subscriberModel->isSuspended($this->firstEmail));
	}

	public function testFinalBounce(): void
	{
		$this->model->incrementBounce($this->firstEmail);
		$this->model->incrementBounce($this->firstEmail);
		$this->model->incrementBounce($this->firstEmail);

		$this->assertTrue($this->subscriberModel->isSuspended($this->firstEmail));
		$this->assertSame(0, $this->model->getBounceCount($this->firstEmail));
		$this->assertSame(['soft_bounce'], $this->subscriberModel->getReasons($this->firstEmail));
	}

}
