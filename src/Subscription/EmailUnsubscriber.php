<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Subscription;

interface EmailUnsubscriber
{

	public function unsubscribe(string $email): void;

}
