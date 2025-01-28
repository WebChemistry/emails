<?php declare(strict_types = 1);

namespace Tests\Webhook;

use Tests\DatabaseEnvironment;
use Tests\TestCase;
use Tests\WebhookEnvironment;
use WebChemistry\Emails\Adapter\Webhook\MailgunWebhookProcessor;
use WebChemistry\Emails\EmailManager;

final class MailgunWebhookProcessorTest extends TestCase
{

	use DatabaseEnvironment;
	use WebhookEnvironment;

	private string $email = 'alice@example.com';

	private MailgunWebhookProcessor $webhook;

	public function setUp(): void
	{
		$this->webhook = new MailgunWebhookProcessor('secret');
	}

	public function testInvalidMethod(): void
	{
		$request = $this->createInvalidRequest(method: true);

		$code = $this->webhook->process($this->manager, $request, EmailManager::SectionTransactional);

		$this->assertSame($this->webhook::MethodNotAllowed, $code);
	}

	public function testEmptyBody(): void
	{
		$request = $this->createInvalidRequest(emptyBody: true);

		$code = $this->webhook->process($this->manager, $request, EmailManager::SectionTransactional);

		$this->assertSame($this->webhook::BadRequest, $code);
	}

	public function testInvalidJson(): void
	{
		$request = $this->createInvalidRequest(invalidJson: true);

		$code = $this->webhook->process($this->manager, $request, EmailManager::SectionTransactional);

		$this->assertSame($this->webhook::BadRequest, $code);
	}

	public function testSignatureMismatch(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/signature_mismatch.http');

		$code = $this->webhook->process($this->manager, $request, EmailManager::SectionTransactional);

		$this->assertSame($this->webhook::InvalidSignature, $code);
	}

	public function testSpamComplaint(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/spam_complaint.http');

		$code = $this->webhook->process($this->manager, $request, EmailManager::SectionTransactional);

		$this->assertSame($this->webhook::Success, $code);
		$this->assertSame([EmailManager::SuspensionTypeSpamComplaint], $this->manager->getSuspensionReasons($this->email, EmailManager::SectionTransactional));
	}

	public function testOpen(): void
	{
		$this->inactivityModel->incrementCounter($this->email, EmailManager::SectionTransactional);

		$this->assertSame(1, $this->inactivityModel->getCount($this->email, EmailManager::SectionTransactional));

		$request = $this->createRequest(__DIR__ . '/mailgun/open.http');

		$code = $this->webhook->process($this->manager, $request, EmailManager::SectionTransactional);

		$this->assertSame($this->webhook::Success, $code);
		$this->assertSame(0, $this->inactivityModel->getCount($this->email, EmailManager::SectionTransactional));
	}

	public function testHardBounce(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/permanent_failure.http');

		$code = $this->webhook->process($this->manager, $request, EmailManager::SectionTransactional);

		$this->assertSame($this->webhook::Success, $code);
		$this->assertTrue($this->manager->isSuspended($this->email, EmailManager::SectionTransactional));
		$this->assertSame([EmailManager::SuspensionTypeHardBounce], $this->manager->getSuspensionReasons($this->email, EmailManager::SectionTransactional));
	}

	public function testSoftBounce(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/soft_bounce.http');

		$code = $this->webhook->process($this->manager, $request, EmailManager::SectionTransactional);

		$this->assertSame($this->webhook::Success, $code);
		$this->assertFalse($this->manager->isSuspended($this->email, EmailManager::SectionTransactional));

		$this->assertSame(1, $this->softBounceModel->getBounceCount($this->email));
	}

	public function testUnsubscribe(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/unsubscribe.http');

		$code = $this->webhook->process($this->manager, $request, EmailManager::SectionTransactional);

		$this->assertSame($this->webhook::Success, $code);
		$this->assertTrue($this->manager->isSuspended($this->email, EmailManager::SectionTransactional));
		$this->assertSame([EmailManager::SuspensionTypeUnsubscribe], $this->manager->getSuspensionReasons($this->email, EmailManager::SectionTransactional));
	}

}
