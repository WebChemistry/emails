<?php declare(strict_types = 1);

namespace Tests\Model;

use Tests\DatabaseEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Model\SuspensionModel;
use WebChemistry\Emails\Type\SuspensionType;

final class SuspensionModelTest extends TestCase
{

	use DatabaseEnvironment;

	private SuspensionModel $model;

	protected function setUp(): void
	{
		$this->model = new SuspensionModel($this->registry);
	}

	public function testNoSuspensions(): void
	{
		$this->assertFalse($this->model->isSuspended($this->firstEmail));
		$this->assertSame([], $this->model->getReasons($this->firstEmail));
	}

	public function testSingleEmailSuspension(): void
	{
		$this->model->suspend($this->firstEmail, SuspensionType::SpamComplaint);

		$this->assertTrue($this->model->isSuspended($this->firstEmail));
		$this->assertSame([SuspensionType::SpamComplaint], $this->model->getReasons($this->firstEmail));
	}

	public function testTwoReasons(): void
	{
		$this->model->suspend($this->firstEmail, SuspensionType::SpamComplaint);
		$this->model->suspend($this->firstEmail, SuspensionType::HardBounce);

		$this->assertTrue($this->model->isSuspended($this->firstEmail));
		$this->assertSame($this->sort([SuspensionType::SpamComplaint, SuspensionType::HardBounce]), $this->sort($this->model->getReasons($this->firstEmail)));
	}

	public function testActivate(): void
	{
		$this->model->suspend($this->firstEmail, SuspensionType::SpamComplaint);
		$this->model->suspend($this->firstEmail, SuspensionType::HardBounce);
		$this->model->suspend($this->firstEmail, SuspensionType::SoftBounce);

		$this->model->activate($this->firstEmail);

		$this->assertTrue($this->model->isSuspended($this->firstEmail));
		$this->assertSame([SuspensionType::SpamComplaint], $this->model->getReasons($this->firstEmail));
	}

	public function testClear(): void
	{
		$this->model->suspend($this->firstEmail, SuspensionType::SpamComplaint);
		$this->model->suspend($this->firstEmail, SuspensionType::HardBounce);
		$this->model->suspend($this->firstEmail, SuspensionType::SoftBounce);

		$this->model->reset($this->firstEmail);

		$this->assertFalse($this->model->isSuspended($this->firstEmail));
		$this->assertSame([], $this->model->getReasons($this->firstEmail));
	}

	public function testRemoveMethods(): void
	{
		$this->model->suspend($this->firstEmail, SuspensionType::SpamComplaint);
		$this->model->suspend($this->secondEmail, SuspensionType::HardBounce);

		$this->assertSame([$this->thirdEmail], $this->model->removeSuspendedEmailsFrom([$this->firstEmail, $this->secondEmail, $this->thirdEmail]));
		$this->assertSame([$this->thirdEmailAccount], $this->model->removeSuspendedEmailAccountsFrom([$this->firstEmailAccount, $this->secondEmailAccount, $this->thirdEmailAccount]));
	}

	/**
	 * @param mixed[] $values
	 * @return mixed[]
	 */
	private function sort(array $values): array
	{
		sort($values);

		return $values;
	}

}
