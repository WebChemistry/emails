<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Webhook;

use WebChemistry\Emails\EmailManager;

interface WebhookProcessor
{

	public const Success = 0;
	public const InvalidSignature = 1;
	public const BadRequest = 2;
	public const MethodNotAllowed = 3;

	public function process(EmailManager $manager, WebhookRequest $request, string $section): int;

}
