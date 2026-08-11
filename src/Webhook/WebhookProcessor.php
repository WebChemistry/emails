<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Webhook;

use WebChemistry\Emails\EmailManager;

interface WebhookProcessor
{

	/**
	 * @param string $section Section to attribute an event to when the payload does not name one itself.
	 */
	public function process(EmailManager $manager, WebhookRequest $request, string $section): WebhookResult;

}
