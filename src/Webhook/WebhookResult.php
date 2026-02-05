<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Webhook;

enum WebhookResult
{

	case Success;
	case InvalidSignature;
	case BadRequest;
	case MethodNotAllowed;

}
