<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Webhook;

use WebChemistry\Emails\EmailManager;

interface WebhookProcessor
{

	public function process(EmailManager $manager, WebhookRequest $request, string $section): WebhookResult;

}
