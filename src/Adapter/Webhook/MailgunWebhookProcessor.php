<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter\Webhook;

use SensitiveParameter;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Webhook\WebhookProcessor;
use WebChemistry\Emails\Webhook\WebhookRequest;
use WebChemistry\Emails\Webhook\WebhookResult;

final class MailgunWebhookProcessor implements WebhookProcessor
{

	public function __construct(
		#[SensitiveParameter]
		private string $secret,
	)
	{
	}

	public function process(EmailManager $manager, WebhookRequest $request, string $section): WebhookResult
	{
		if ($request->isEmptyBody()) {
			return WebhookResult::BadRequest;
		}

		if (!$request->isPostMethod()) {
			return WebhookResult::MethodNotAllowed;
		}

		$json = json_decode($request->body, true);

		if (json_last_error()) {
			return WebhookResult::BadRequest;
		}

		if (!is_array($json['signature'] ?? null)) {
			return WebhookResult::BadRequest;
		}

		$token = $this->toString($json['signature']['token'] ?? null);
		$timestamp = $this->toString($json['signature']['timestamp'] ?? null);
		$signature = $this->toString($json['signature']['signature'] ?? null);

		$expectedSignature = hash_hmac('sha256', $timestamp . $token, $this->secret);

		if (!hash_equals($expectedSignature, $signature)) {
			return WebhookResult::InvalidSignature;
		}

		$payload = $json['event-data'] ?? null;

		if (!is_array($payload)) {
			return WebhookResult::BadRequest;
		}


		$event = $this->toString($payload['event'] ?? null);
		$email = $this->toString($payload['recipient'] ?? null);
		$severity = $this->toString($payload['severity'] ?? null);

		if ($event === '' || $email === '') {
			return WebhookResult::BadRequest;
		}

		if ($event === 'opened') {
			$manager->emailOpened($email, $section);
		} else if ($event === 'unsubscribed') {
			$manager->unsubscribe($email, $section);
		} else if ($event === 'failed' && $severity === 'permanent') {
			$bounceType = $payload['delivery-status']['bounce-type'] ?? null;

			if ($bounceType === 'soft') {
				$manager->softBounce($email);
			} else if ($bounceType === 'hard') {
				$manager->hardBounce($email);
			}
		} else if ($event === 'complained') {
			$manager->spamComplaint($email);
		}

		return WebhookResult::Success;
	}

	private function toString(mixed $value): string
	{
		return is_scalar($value) ? (string) $value : '';
	}

}
