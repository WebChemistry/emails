<?php declare(strict_types = 1);

namespace Tests;

use WebChemistry\Emails\EmailManager;

final class EmailManagerTest extends TestCase
{

	use EmailManagerEnvironment;

	public function testSuccessfulUnsubscribe(): void
	{
		$link = $this->unsubscribeManager->addToLink('http://example.com', $this->firstEmail);

		$this->manager->tryToUnsubscribeFromLink($link);

		$this->assertSame([EmailManager::SuspensionTypeUnsubscribe], $this->subscriberModel->getReasons($this->firstEmail));
	}

	public function testSuccessfulSectionUnsubscribe(): void
	{
		$link = $this->unsubscribeManager->addToLink('http://example.com', $this->firstEmail, EmailManager::SectionTransactional);

		$this->manager->tryToUnsubscribeFromLink($link);

		$this->assertSame([EmailManager::SuspensionTypeUnsubscribe], $this->subscriberModel->getReasons($this->firstEmail, EmailManager::SectionTransactional));
		$this->assertSame([], $this->subscriberModel->getReasons($this->firstEmail));
	}

	public function testUnsuccessfulUnsubscribe(): void
	{
		$link = $this->unsubscribeManager->addToLink('http://example.com', $this->firstEmail);

		$this->manager->tryToUnsubscribeFromLink(substr($link, 0, -1));

		$this->assertSame([], $this->subscriberModel->getReasons($this->firstEmail));
	}

}
