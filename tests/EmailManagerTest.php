<?php declare(strict_types = 1);

namespace Tests;

use WebChemistry\Emails\EmailAccount;

final class EmailManagerTest extends TestCase
{

	use EmailManagerEnvironment;

	public function testSuccessfulUnsubscribe(): void
	{
		$link = $this->manager->addUnsubscribeQueryParameter('http://example.com', $this->firstEmail, 'notifications');

		$this->manager->processSubscribeUnsubscribeQueryParameter($link);

		$this->assertFalse($this->manager->canSend($this->firstEmail, 'notifications'));
	}

	public function testSuccessfulResubscribe(): void
	{
		$this->manager->unsubscribe($this->firstEmail, 'notifications');
		$link = $this->manager->addResubscribeQueryParameter('http://example.com', $this->firstEmail, 'notifications');

		$this->manager->processSubscribeUnsubscribeQueryParameter($link);

		$this->assertTrue($this->manager->canSend($this->firstEmail, 'notifications'));
	}

	public function testUnsuccessfulUnsubscribe(): void
	{
		$link = $this->manager->addUnsubscribeQueryParameter('http://example.com', $this->firstEmail, 'notifications');

		$this->manager->processSubscribeUnsubscribeQueryParameter(substr($link, 0, -1));

		$this->assertTrue($this->manager->canSend($this->firstEmail, 'notifications'));
	}

	public function testReset(): void
	{
		$this->manager->unsubscribe($this->firstEmail, 'notifications');

		$this->manager->softBounce($this->firstEmail);
		$this->manager->hardBounce($this->firstEmail);

		$this->manager->reset($this->firstEmail);

		$this->assertTrue($this->manager->canSend($this->firstEmail, 'notifications'));
		$this->assertSame(0, $this->softBounceModel->getBounceCount($this->firstEmail));
	}

	public function testEmailAccountsForDelivery(): void
	{
		$this->manager->spamComplaint($this->firstEmail);

		$accounts = $this->manager->filterEmailAccountsForDelivery([
			new EmailAccount($this->firstEmail),
			new EmailAccount($this->secondEmail),
		], 'notifications');

		$this->assertCount(1, $accounts);
		$this->assertEquals($this->secondEmail, $accounts[0]->email);
	}

	public function testEmailAccountsForDeliveryEmailIsUnsubscribed(): void
	{
		$this->manager->unsubscribe($this->firstEmail, 'notifications');

		$accounts = $this->manager->filterEmailAccountsForDelivery([
			new EmailAccount($this->firstEmail),
			new EmailAccount($this->secondEmail),
		], 'notifications');

		$this->assertCount(1, $accounts);
		$this->assertEquals($this->secondEmail, $accounts[0]->email);
	}

	public function testEmailsForDelivery(): void
	{
		$this->manager->spamComplaint($this->firstEmail);

		$accounts = $this->manager->filterEmailsForDelivery([
			$this->firstEmail,
			$this->secondEmail,
		], 'notifications');

		$this->assertCount(1, $accounts);
		$this->assertEquals($this->secondEmail, $accounts[0]);
	}

}
