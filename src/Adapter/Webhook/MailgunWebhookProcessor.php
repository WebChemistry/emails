<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter\Webhook;

use SensitiveParameter;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Webhook\WebhookProcessor;
use WebChemistry\Emails\Webhook\WebhookRequest;

final class MailgunWebhookProcessor implements WebhookProcessor
{

	public function __construct(
		#[SensitiveParameter]
		private string $secret,
	)
	{
	}

	public function process(EmailManager $manager, WebhookRequest $request, string $section): int
	{
		if ($request->isEmptyBody()) {
			return self::BadRequest;
		}

		if (!$request->isPostMethod()) {
			return self::BadRequest;
		}

		$json = json_decode($request->body, true);

		if (json_last_error()) {
			return self::BadRequest;
		}

		if (!is_array($json['signature'] ?? null)) {
			return self::BadRequest;
		}

		$token = $this->toString($json['signature']['token'] ?? null);
		$timestamp = $this->toString($json['signature']['timestamp'] ?? null);
		$signature = $this->toString($json['signature']['signature'] ?? null);

		$expectedSignature = hash_hmac('sha256', $timestamp . $token, $this->secret);

		if (!hash_equals($expectedSignature, $signature)) {
			return self::InvalidSignature;
		}

		$payload = $json['event-data'] ?? null;

		if (!is_array($payload)) {
			return self::BadRequest;
		}


		$event = $this->toString($payload['event'] ?? null);
		$email = $this->toString($payload['recipient'] ?? null);
		$severity = $this->toString($payload['severity'] ?? null);

		if ($event === '' || $email === '') {
			return self::BadRequest;
		}

		if ($event === 'open') {
			$manager->recordOpenActivity($email, $section);
		} else if ($event === 'unsubscribed') {
			$manager->unsubscribe($email, $section);
		} else if ($event === 'failed') {
			if ($severity === 'temporary') {
				$manager->softBounce($email);
			} else if ($severity === 'permanent') {
				$manager->hardBounce($email);
			}
		} else if ($event === 'complained') {
			$manager->spamComplaint($email);
		}

		return self::Success;
	}

	private function toString(mixed $value): string
	{
		return is_scalar($value) ? (string) $value : '';
	}

}
