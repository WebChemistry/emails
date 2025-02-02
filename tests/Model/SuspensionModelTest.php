<?php declare(strict_types = 1);

namespace Tests\Model;

use Tests\DatabaseEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Model\SuspensionModel;
use WebChemistry\Emails\Section\Section;
use WebChemistry\Emails\Type\SuspensionType;

final class SuspensionModelTest extends TestCase
{

	use DatabaseEnvironment;

	private SuspensionModel $model;

	private Section $section;

	protected function setUp(): void
	{
		$this->section = new Section('notifications');
		$this->model = new SuspensionModel($this->connectionAccessor);
	}

	public function testNoSuspensions(): void
	{
		$this->assertFalse($this->model->isSuspended($this->firstEmail, $this->section));
		$this->assertSame([], $this->model->getReasons($this->firstEmail));
	}

	public function testSingleEmailSuspension(): void
	{
		$this->model->suspend($this->firstEmail, SuspensionType::SpamComplaint);

		$this->assertTrue($this->model->isSuspended($this->firstEmail, $this->section));
		$this->assertSame([SuspensionType::SpamComplaint], $this->model->getReasons($this->firstEmail));
	}

	public function testTwoReasons(): void
	{
		$this->model->suspend($this->firstEmail, SuspensionType::SpamComplaint);
		$this->model->suspend($this->firstEmail, SuspensionType::HardBounce);

		$this->assertTrue($this->model->isSuspended($this->firstEmail, $this->section));
		$this->assertSame($this->sort([SuspensionType::SpamComplaint, SuspensionType::HardBounce]), $this->sort($this->model->getReasons($this->firstEmail)));
	}

	public function testSuspendEssentialSectionViaSpamComplaint(): void
	{
		$this->model->suspend($this->firstEmail, SuspensionType::SpamComplaint);

		$this->assertFalse($this->model->isSuspended($this->firstEmail, new Section(Section::Essential)));
	}

	public function testSuspendEssentialSectionViaHardBounce(): void
	{
		$this->model->suspend($this->firstEmail, SuspensionType::HardBounce);

		$this->assertTrue($this->model->isSuspended($this->firstEmail, new Section(Section::Essential)));
	}

	public function testSuspendEssentialSectionViaSoftBounce(): void
	{
		$this->model->suspend($this->firstEmail, SuspensionType::SoftBounce);

		$this->assertFalse($this->model->isSuspended($this->firstEmail, new Section(Section::Essential)));
	}

	public function testActivate(): void
	{
		$this->model->suspend($this->firstEmail, SuspensionType::SpamComplaint);
		$this->model->suspend($this->firstEmail, SuspensionType::HardBounce);
		$this->model->suspend($this->firstEmail, SuspensionType::SoftBounce);

		$this->model->activate($this->firstEmail);

		$this->assertTrue($this->model->isSuspended($this->firstEmail, $this->section));
		$this->assertSame([SuspensionType::SpamComplaint], $this->model->getReasons($this->firstEmail));
	}

	public function testClear(): void
	{
		$this->model->suspend($this->firstEmail, SuspensionType::SpamComplaint);
		$this->model->suspend($this->firstEmail, SuspensionType::HardBounce);
		$this->model->suspend($this->firstEmail, SuspensionType::SoftBounce);

		$this->model->reset($this->firstEmail);

		$this->assertFalse($this->model->isSuspended($this->firstEmail, $this->section));
		$this->assertSame([], $this->model->getReasons($this->firstEmail));
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
