<?php declare(strict_types = 1);

namespace Tests;

use WebChemistry\Emails\EmailManager;

final class EmailManagerTest extends TestCase
{

	use EmailManagerEnvironment;

	public function testSuccessfulUnsubscribe(): void
	{
		$link = $this->unsubscribeManager->addUnsubscribeQueryParameter('http://example.com', $this->firstEmail);

		$this->manager->processSubscriptionLink($link);

		$this->assertSame([EmailManager::SuspensionTypeUnsubscribe], $this->subscriberModel->getReasons($this->firstEmail));
	}

	public function testSuccessfulSectionUnsubscribe(): void
	{
		$link = $this->unsubscribeManager->addUnsubscribeQueryParameter('http://example.com', $this->firstEmail, EmailManager::SectionTransactional);

		$this->manager->processSubscriptionLink($link);

		$this->assertSame([EmailManager::SuspensionTypeUnsubscribe], $this->subscriberModel->getReasons($this->firstEmail, EmailManager::SectionTransactional));
		$this->assertSame([], $this->subscriberModel->getReasons($this->firstEmail));
	}

	public function testUnsuccessfulUnsubscribe(): void
	{
		$link = $this->unsubscribeManager->addUnsubscribeQueryParameter('http://example.com', $this->firstEmail);

		$this->manager->processSubscriptionLink(substr($link, 0, -1));

		$this->assertSame([], $this->subscriberModel->getReasons($this->firstEmail));
	}

	public function testClear(): void
	{
		$this->subscriberModel->unsubscribe($this->firstEmail, EmailManager::SuspensionTypeUnsubscribe);
		$this->subscriberModel->unsubscribe($this->firstEmail, EmailManager::SuspensionTypeInactivity);

		$this->manager->softBounce($this->firstEmail);
		$this->manager->hardBounce($this->firstEmail);

		$this->manager->clearRecords($this->firstEmail);

		$this->assertSame([], $this->subscriberModel->getReasons($this->firstEmail));
		$this->assertSame(0, $this->softBounceModel->getBounceCount($this->firstEmail));
	}

}
