<?php declare(strict_types = 1);

namespace Tests\Model;

use Tests\DatabaseEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Model\SubscriberModel;

final class SubscriberModelTest extends TestCase
{

	use DatabaseEnvironment;

	public const TableSql = 'CREATE TABLE IF NOT EXISTS email_suspensions (email VARCHAR, type VARCHAR, section VARCHAR, created_at DATETIME, PRIMARY KEY(email, type, section));';

	private SubscriberModel $model;

	private static function getInitialSql(): string
	{
		return self::TableSql;
	}

	protected function setUp(): void
	{
		$this->model = new SubscriberModel($this->registry);
	}

	public function testNoSuspensions(): void
	{
		$this->assertFalse($this->model->isSuspended($this->firstEmail));
		$this->assertSame([$this->firstEmail, $this->secondEmail], $this->model->clearFromSuspended([$this->firstEmail, $this->secondEmail]));
		$this->assertSame([], $this->model->getReasons($this->firstEmail));
	}

	public function testSingleEmailSuspension(): void
	{
		$this->model->unsubscribe($this->firstEmail, 'unsubscribe');

		$this->assertTrue($this->model->isSuspended($this->firstEmail));
		$this->assertSame([$this->secondEmail], $this->model->clearFromSuspended([$this->firstEmail, $this->secondEmail]));
		$this->assertSame(['unsubscribe'], $this->model->getReasons($this->firstEmail));
	}

	public function testSingleEmailSuspensionWithSection(): void
	{
		$this->model->unsubscribe($this->firstEmail, 'unsubscribe', 'section');

		$this->assertTrue($this->model->isSuspended($this->firstEmail, 'section'));
		$this->assertSame([$this->secondEmail], $this->model->clearFromSuspended([$this->firstEmail, $this->secondEmail], 'section'));
		$this->assertSame(['unsubscribe'], $this->model->getReasons($this->firstEmail, 'section'));
	}

	public function testSingleEmailSuspensionWithDifferentSection(): void
	{
		$this->model->unsubscribe($this->firstEmail, 'unsubscribe', 'section');

		$this->assertFalse($this->model->isSuspended($this->firstEmail, 'section2'));
		$this->assertSame([$this->firstEmail, $this->secondEmail], $this->model->clearFromSuspended([$this->firstEmail, $this->secondEmail], 'section2'));
		$this->assertSame([], $this->model->getReasons($this->firstEmail, 'section2'));
	}

	public function testUnsuccessfulResubscribeFromGlobal(): void
	{
		$this->model->unsubscribe($this->firstEmail, 'unsubscribe', 'section');
		$this->model->unsubscribe($this->firstEmail, 'unsubscribe');
		$this->model->unsubscribe($this->firstEmail, 'hard_bounce');

		$this->model->resubscribe($this->firstEmail);

		$this->assertTrue($this->model->isSuspended($this->firstEmail, 'section'));
		$this->assertSame(['hard_bounce', 'unsubscribe'], $this->model->getReasons($this->firstEmail, 'section'));
	}

	public function testResubscribeFromGlobalAndSection(): void
	{
		$this->model->unsubscribe($this->firstEmail, 'unsubscribe', 'section');
		$this->model->unsubscribe($this->firstEmail, 'hard_bounce');

		$this->model->resubscribe($this->firstEmail, ['unsubscribe', 'hard_bounce'], 'section');

		$this->assertFalse($this->model->isSuspended($this->firstEmail, 'section'));
		$this->assertFalse($this->model->isSuspended($this->firstEmail));
		$this->assertSame([], $this->model->getReasons($this->firstEmail, 'section'));
	}

	public function testSingleEmailSuspensionWithGlobalSection(): void
	{
		$this->model->unsubscribe($this->firstEmail, 'hard_bounce');

		$this->assertTrue($this->model->isSuspended($this->firstEmail, 'section2'));
		$this->assertSame([$this->secondEmail], $this->model->clearFromSuspended([$this->firstEmail, $this->secondEmail], 'section2'));
		$this->assertSame(['hard_bounce'], $this->model->getReasons($this->firstEmail, 'section2'));
	}

	public function testSingleEmailMultipleSameSuspensions(): void
	{
		$this->model->unsubscribe($this->firstEmail, 'unsubscribe');
		$this->model->unsubscribe($this->firstEmail, 'unsubscribe');

		$this->assertTrue($this->model->isSuspended($this->firstEmail));
		$this->assertSame([$this->secondEmail], $this->model->clearFromSuspended([$this->firstEmail, $this->secondEmail]));
	}

	public function testSingleEmailMultipleDiffSuspensions(): void
	{
		$this->model->unsubscribe($this->firstEmail, 'unsubscribe');
		$this->model->unsubscribe($this->firstEmail, 'hard_bounce');

		$this->assertTrue($this->model->isSuspended($this->firstEmail));
		$this->assertSame([$this->secondEmail], $this->model->clearFromSuspended([$this->firstEmail, $this->secondEmail]));
		$this->assertEquals($this->sort(['unsubscribe', 'hard_bounce']), $this->sort($this->model->getReasons($this->firstEmail)));
	}

	public function testMultipleEmailSuspensions(): void
	{
		$this->model->unsubscribe([$this->firstEmail, $this->secondEmail], 'unsubscribe');

		$this->assertTrue($this->model->isSuspended($this->firstEmail));
		$this->assertTrue($this->model->isSuspended($this->secondEmail));

		$this->assertSame([], $this->model->clearFromSuspended([$this->firstEmail, $this->secondEmail]));
	}

	public function testEmailSuccessfulDefaultResubscribe(): void
	{
		$this->model->unsubscribe($this->firstEmail, 'unsubscribe');
		$this->model->resubscribe($this->firstEmail);

		$this->assertFalse($this->model->isSuspended($this->firstEmail));
		$this->assertSame([$this->firstEmail, $this->secondEmail], $this->model->clearFromSuspended([$this->firstEmail, $this->secondEmail]));
	}

	public function testEmailSuccessfulDefaultResubscribeMultiple(): void
	{
		$this->model->unsubscribe($this->firstEmail, 'unsubscribe');
		$this->model->unsubscribe($this->firstEmail, 'inactivity');
		$this->model->resubscribe($this->firstEmail);

		$this->assertFalse($this->model->isSuspended($this->firstEmail));
		$this->assertSame([$this->firstEmail, $this->secondEmail], $this->model->clearFromSuspended([$this->firstEmail, $this->secondEmail]));
	}

	public function testEmailUnsuccessfulDefaultResubscribe(): void
	{
		$this->model->unsubscribe($this->firstEmail, 'hard_bounce');
		$this->model->unsubscribe($this->firstEmail, 'unsubscribe');
		$this->model->unsubscribe($this->firstEmail, 'inactivity');
		$this->model->resubscribe($this->firstEmail);

		$this->assertTrue($this->model->isSuspended($this->firstEmail));
		$this->assertSame([$this->secondEmail], $this->model->clearFromSuspended([$this->firstEmail, $this->secondEmail]));
		$this->assertSame(['hard_bounce'], $this->model->getReasons($this->firstEmail));
	}

	public function testEmailSuccessfulCustomResubscribe(): void
	{
		$this->model->unsubscribe($this->firstEmail, 'hard_bounce');
		$this->model->unsubscribe($this->firstEmail, 'spam_complaint');
		$this->model->resubscribe($this->firstEmail, ['hard_bounce', 'spam_complaint']);

		$this->assertFalse($this->model->isSuspended($this->firstEmail));
		$this->assertSame([$this->firstEmail, $this->secondEmail], $this->model->clearFromSuspended([$this->firstEmail, $this->secondEmail]));
	}

	/**
	 * @param string[] $values
	 * @return string[]
	 */
	private function sort(array $values): array
	{
		sort($values, SORT_STRING);

		return $values;
	}

}
