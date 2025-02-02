<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

use WebChemistry\Emails\Section\SectionCategory;

interface EmailManager
{

	/**
	 * @param string[]|string $emails
	 */
	public function hardBounce(array|string $emails): void;

	/**
	 * @param string[]|string $emails
	 */
	public function unsubscribe(array|string $emails, string $section, string $category = SectionCategory::Global): void;


	public function addResubscribeQueryParameter(string $link, string $email, string $section, string $category = SectionCategory::Global): string;

	public function addUnsubscribeQueryParameter(string $link, string $email, string $section, string $category = SectionCategory::Global): string;

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
	public function emailOpened(array|string $emails, string $section): void;

	public function beforeEmailSent(EmailRegistry $registry, string $section, string $category = SectionCategory::Global): void;

	public function afterEmailSent(EmailRegistry $registry, string $section, string $category = SectionCategory::Global): void;

	/**
	 * Checks if the email can be sent to the recipient.
	 */
	public function canSend(string $email, string $section, string $category = SectionCategory::Global): bool;

	/**
	 * Resets the suspension status, soft bounces, inactivity and unsubscribes.
	 *
	 * @param string[]|string $emails
	 */
	public function reset(array|string $emails): void;

	/**
	 * @param string[]|string $emails
	 */
	public function inactive(array|string $emails, string $section): void;

}
