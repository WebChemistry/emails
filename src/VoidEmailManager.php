<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

final class VoidEmailManager implements EmailManager
{

	public function hardBounce(array|string $emails): void
	{
	}

	public function unsubscribe(array|string $emails, string $section): void
	{
	}

	public function processSubscriptionLink(string $link): void
	{
	}

	public function resubscribe(string $email, string $section): void
	{
	}

	public function softBounce(string $email): void
	{
	}

	public function spamComplaint(array|string $emails): void
	{
	}

	public function recordOpenActivity(array|string $emails, string $section): void
	{
	}

	public function recordSentActivity(array|string $emails, string $section): void
	{
	}

	public function getSuspensionReasons(string $email, string $section): array
	{
		return [];
	}

	public function isSuspended(string $email, string $section): bool
	{
		return false;
	}

	public function clearFromSuspendedAccounts(array $accounts, string $section): array
	{
		return $accounts;
	}

	public function clearRecords(array|string $emails): void
	{
	}

}
