<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

interface EmailManager
{

	public const SectionGlobal = 'global';
	public const SectionTransactional = 'transactional';
	public const SectionMarketing = 'marketing';

	public const SuspensionTypeHardBounce = 'hard_bounce';
	public const SuspensionTypeSoftBounce = 'soft_bounce';
	public const SuspensionTypeSpamComplaint = 'spam_complaint';
	public const SuspensionTypeUnsubscribe = 'unsubscribe';
	public const SuspensionTypeInactivity = 'inactivity';
	public const SuspensionTypes = ['hard_bounce', 'soft_bounce', 'spam_complaint', 'unsubscribe', 'inactivity'];
	public const SuspensionResubscribeTypes = ['unsubscribe', 'inactivity'];

	/**
	 * @param string[]|string $emails
	 */
	public function hardBounce(array|string $emails): void;

	/**
	 * @param string[]|string $emails
	 */
	public function unsubscribe(array|string $emails, string $section): void;

	public function processSubscriptionLink(string $link): void;

	public function resubscribe(string $email, string $section): void;

	public function softBounce(string $email): void;

	/**
	 * @param string[]|string $emails
	 */
	public function spamComplaint(array|string $emails): void;

	/**
	 * @param string[]|string $emails
	 */
	public function recordOpenActivity(array|string $emails, string $section): void;

	/**
	 * @param string[]|string $emails
	 */
	public function recordSentActivity(array|string $emails, string $section): void;

	/**
	 * @return string[]
	 */
	public function getSuspensionReasons(string $email, string $section): array;

	public function isSuspended(string $email, string $section): bool;

	/**
	 * @param EmailAccount[] $accounts
	 * @return EmailAccount[]
	 */
	public function clearFromSuspendedAccounts(array $accounts, string $section): array;

	/**
	 * @param string[]|string $emails
	 */
	public function clearRecords(array|string $emails): void;

}
