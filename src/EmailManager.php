<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

interface EmailManager
{

	public const SectionGlobal = 'global';
	public const SectionEssential = 'essential';

	public const GlobalCategory = '*';

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
	public function unsubscribe(array|string $emails, string $section, string $category = self::GlobalCategory): void;


	public function addResubscribeQueryParameter(string $link, string $email, string $section, string $category = EmailManager::GlobalCategory): string;

	public function addUnsubscribeQueryParameter(string $link, string $email, string $section, string $category = EmailManager::GlobalCategory): string;

	public function processSubscribeUnsubscribeQueryParameter(string $link): void;

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
	 * Checks if the email can be sent to the recipient.
	 */
	public function canSend(string $email, string $section, string $category = EmailManager::GlobalCategory): bool;


	/**
	 * Returns only email accounts that can be delivered.
	 *
	 * @param EmailAccount[] $accounts
	 * @return EmailAccount[]
	 */
	public function filterEmailAccountsForDelivery(array $accounts, string $section, string $category = EmailManager::GlobalCategory): array;

	/**
	 * Returns only emails that can be delivered.
	 *
	 * @param string[] $emails
	 * @return string[]
	 */
	public function filterEmailsForDelivery(array $emails, string $section, string $category = EmailManager::GlobalCategory): array;

	/**
	 * Resets the suspension status, soft bounces, inactivity and unsubscribes.
	 *
	 * @param string[]|string $emails
	 */
	public function reset(array|string $emails): void;

}
