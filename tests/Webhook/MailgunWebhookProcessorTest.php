<?php declare(strict_types = 1);

namespace Tests\Webhook;

use Tests\DatabaseEnvironment;
use Tests\TestCase;
use Tests\WebhookEnvironment;
use WebChemistry\Emails\Adapter\Webhook\MailgunWebhookProcessor;
use WebChemistry\Emails\Type\SuspensionType;

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

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame($this->webhook::MethodNotAllowed, $code);
	}

	public function testEmptyBody(): void
	{
		$request = $this->createInvalidRequest(emptyBody: true);

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame($this->webhook::BadRequest, $code);
	}

	public function testInvalidJson(): void
	{
		$request = $this->createInvalidRequest(invalidJson: true);

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame($this->webhook::BadRequest, $code);
	}

	public function testSignatureMismatch(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/signature_mismatch.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame($this->webhook::InvalidSignature, $code);
	}

	public function testSpamComplaint(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/spam_complaint.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame($this->webhook::Success, $code);
		$this->assertSame([SuspensionType::SpamComplaint], $this->suspensionModel->getReasons($this->email));
	}

	public function testOpen(): void
	{
		$this->inactivityModel->incrementCounter($this->email, $this->sections->getSection('notifications'));

		$this->assertSame(1, $this->inactivityModel->getCount($this->email, $this->sections->getSection('notifications')));

		$request = $this->createRequest(__DIR__ . '/mailgun/open.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame($this->webhook::Success, $code);
		$this->assertSame(0, $this->inactivityModel->getCount($this->email, $this->sections->getSection('notifications')));
	}

	public function testHardBounce(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/hard_bounce.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame($this->webhook::Success, $code);
		$this->assertFalse($this->manager->canSend($this->email, 'notifications'));
		$this->assertSame([SuspensionType::HardBounce], $this->suspensionModel->getReasons($this->email));
	}

	public function testSoftBounce(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/soft_bounce.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame($this->webhook::Success, $code);
		$this->assertTrue($this->manager->canSend($this->email, 'notifications'));

		$this->assertSame(1, $this->softBounceModel->getBounceCount($this->email));
	}

	public function testUnsubscribe(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/unsubscribe.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame($this->webhook::Success, $code);
		$this->assertFalse($this->manager->canSend($this->email, 'notifications'));
		$this->assertFalse($this->subscriptionModel->isSubscribed($this->email, $this->sections->getCategory('notifications')));
	}

}
