<?php declare(strict_types = 1);

namespace Tests;

use GuzzleHttp\Psr7\Message;
use RuntimeException;
use WebChemistry\Emails\Webhook\WebhookRequest;

trait WebhookEnvironment
{

	use EmailManagerEnvironment;

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
