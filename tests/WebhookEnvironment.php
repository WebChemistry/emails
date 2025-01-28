<?php declare(strict_types = 1);

namespace Tests;

use GuzzleHttp\Psr7\Message;
use PHPUnit\Framework\Attributes\Before;
use RuntimeException;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriberModel;
use WebChemistry\Emails\Webhook\WebhookRequest;

trait WebhookEnvironment
{

	use DatabaseEnvironment;

	private SubscriberModel $subscriberModel;

	private InactivityModel $inactivityModel;

	private SoftBounceModel $softBounceModel;

	private EmailManager $manager;

	#[Before(10)]
	public function setUpWebhook(): void
	{
		$this->subscriberModel = new SubscriberModel($this->registry);
		$this->inactivityModel = new InactivityModel(2, $this->registry, $this->subscriberModel);
		$this->softBounceModel = new SoftBounceModel($this->registry, $this->subscriberModel);
		$this->manager = new EmailManager(
			$this->inactivityModel,
			$this->subscriberModel,
			$this->softBounceModel,
		);
	}

	public function createInvalidRequest(bool $method = false, bool $emptyBody = false, bool $invalidJson = false): WebhookRequest
	{
		if ($method) {
			return new WebhookRequest('UNKNOWN', '{}', []);
		}

		if ($emptyBody) {
			return new WebhookRequest('POST', '', []);
		}

		if ($invalidJson) {
			return new WebhookRequest('POST', 'invalid', []);
		}

		throw new RuntimeException('Unexpected');
	}

	public function createRequest(string $file): WebhookRequest
	{
		$contents = file_get_contents($file);

		if ($contents === false) {
			throw new RuntimeException("File $file not found");
		}

		return WebhookRequest::fromPsr(Message::parseRequest($contents));
	}

}
