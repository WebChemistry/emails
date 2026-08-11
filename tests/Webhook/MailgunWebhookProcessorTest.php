<?php declare(strict_types = 1);

namespace Tests\Webhook;

use Tests\DatabaseEnvironment;
use Tests\TestCase;
use Tests\WebhookEnvironment;
use WebChemistry\Emails\Adapter\Webhook\MailgunWebhookProcessor;
use WebChemistry\Emails\Type\SuspensionType;
use WebChemistry\Emails\Webhook\WebhookResult;

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

		$this->assertSame(WebhookResult::MethodNotAllowed, $code);
	}

	public function testEmptyBody(): void
	{
		$request = $this->createInvalidRequest(emptyBody: true);

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame(WebhookResult::BadRequest, $code);
	}

	public function testInvalidJson(): void
	{
		$request = $this->createInvalidRequest(invalidJson: true);

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame(WebhookResult::BadRequest, $code);
	}

	public function testSignatureMismatch(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/signature_mismatch.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame(WebhookResult::InvalidSignature, $code);
	}

	public function testSpamComplaint(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/spam_complaint.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame(WebhookResult::Success, $code);
		$this->assertSame([SuspensionType::SpamComplaint], $this->suspensionModel->getReasons($this->email));
	}

	public function testOpen(): void
	{
		$this->inactivityPlugin->incrementCounter($this->email, $this->sections->getSection('notifications'), 2);

		$this->assertSame(1, $this->inactivityPlugin->getCount($this->email, $this->sections->getSection('notifications')));

		$request = $this->createRequest(__DIR__ . '/mailgun/open.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame(WebhookResult::Success, $code);
		$this->assertSame(0, $this->inactivityPlugin->getCount($this->email, $this->sections->getSection('notifications')));
	}

	public function testHardBounce(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/hard_bounce.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame(WebhookResult::Success, $code);
		$this->assertFalse($this->manager->canSend($this->email, 'notifications'));
		$this->assertSame([SuspensionType::HardBounce], $this->suspensionModel->getReasons($this->email));
	}

	public function testSoftBounce(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/soft_bounce.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame(WebhookResult::Success, $code);
		$this->assertTrue($this->manager->canSend($this->email, 'notifications'));

		$this->assertSame(1, $this->softBounceModel->getBounceCount($this->email));
	}

	public function testUnsubscribe(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/unsubscribe.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame(WebhookResult::Success, $code);
		$this->assertFalse($this->manager->canSend($this->email, 'notifications'));
		$this->assertFalse($this->subscriptionModel->isSubscribed($this->email, $this->sections->getCategory('notifications')));
	}

	/**
	 * The section MailgunAdapter stamped into the custom variables wins over the argument, so an open of a
	 * message from one section stops resetting another section's inactivity counter.
	 */
	public function testOpenIsAttributedToTheSectionFromTheVariables(): void
	{
		$this->inactivityPlugin->incrementCounter($this->email, $this->sections->getSection('notifications'), 2);
		$this->inactivityPlugin->incrementCounter($this->email, $this->sections->getSection('section'), 2);

		$request = $this->createRequest(__DIR__ . '/mailgun/open_section_variable.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame(WebhookResult::Success, $code);
		$this->assertSame(0, $this->inactivityPlugin->getCount($this->email, $this->sections->getSection('section')));
		$this->assertSame(1, $this->inactivityPlugin->getCount($this->email, $this->sections->getSection('notifications')));
	}

	public function testUnsubscribeIsAttributedToTheSectionFromTheVariables(): void
	{
		$request = $this->createRequest(__DIR__ . '/mailgun/unsubscribe_section_variable.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame(WebhookResult::Success, $code);
		$this->assertFalse($this->manager->canSend($this->email, 'section'));
		$this->assertTrue($this->manager->canSend($this->email, 'notifications'));
	}

	/**
	 * A section renamed or dropped since the message went out must not turn a stale event into a failed
	 * webhook - getSection() would throw on it - so it falls back to the section the caller passed.
	 */
	public function testUnknownSectionInTheVariablesFallsBackToTheArgument(): void
	{
		$this->inactivityPlugin->incrementCounter($this->email, $this->sections->getSection('notifications'), 2);

		$request = $this->createRequest(__DIR__ . '/mailgun/open_unknown_section_variable.http');

		$code = $this->webhook->process($this->manager, $request, 'notifications');

		$this->assertSame(WebhookResult::Success, $code);
		$this->assertSame(0, $this->inactivityPlugin->getCount($this->email, $this->sections->getSection('notifications')));
	}

}
