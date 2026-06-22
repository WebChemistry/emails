<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

use WebChemistry\Emails\Link\DecodedResubscribeValue;
use WebChemistry\Emails\Link\DecodedUnsubscribeValue;
use WebChemistry\Emails\Section\SectionCategory;
use WebChemistry\Emails\Section\Sections;
use WebChemistry\Emails\Section\SectionSubscriptionMap;

/**
 * Central entry point for tracking the deliverability and subscription state of e-mail recipients.
 *
 * The manager owns suspensions (hard/soft bounces, spam complaints), per-category subscriptions and
 * the send guard ({@see self::canSend()}). Cross-cutting concerns such as inactivity tracking or
 * last-sent throttling live in plugins that subscribe to the events dispatched by the implementation;
 * the manager itself does not know about them. Methods that exist purely to notify those plugins are
 * marked below.
 */
interface EmailManager
{

	public function getSections(): Sections;

	/**
	 * Permanently suspends the recipient across all sections (the address is considered undeliverable).
	 *
	 * Dispatches an event that lets plugins drop any per-recipient state they keep.
	 *
	 * @param string[]|string $emails
	 */
	public function hardBounce(array|string $emails): void;

	/**
	 * Unsubscribes the recipient from the given category as an explicit user action.
	 *
	 * @param string[]|string $emails
	 */
	public function unsubscribe(array|string $emails, string $section, string $category = SectionCategory::Global): void;

	/**
	 * Builds a view over the recipient's subscription state for every category of the section.
	 */
	public function createSectionSubscriptionMap(string $section, string $email): SectionSubscriptionMap;

	/**
	 * Applies a decoded unsubscribe/resubscribe link payload, dispatching to {@see self::unsubscribe()}
	 * or {@see self::resubscribe()}. A null value (invalid or tampered link) is ignored.
	 */
	public function processDecodedSubscribeValue(DecodedUnsubscribeValue|DecodedResubscribeValue|null $value): void;

	/**
	 * Re-enables a previously unsubscribed recipient for the given category.
	 *
	 * Dispatches an event so plugins can drop any per-recipient state they keep; the inactivity plugin
	 * uses it to reset its counter for the section, giving the returning recipient a clean start.
	 */
	public function resubscribe(string $email, string $section, string $category = SectionCategory::Global): void;

	/**
	 * Records a soft bounce for the recipient. Once the configured bounce limit is reached the address
	 * is suspended and an event is dispatched so plugins can drop any per-recipient state they keep.
	 */
	public function softBounce(string $email): void;

	/**
	 * Suspends the recipient across all sections after a spam complaint.
	 *
	 * Dispatches an event that lets plugins drop any per-recipient state they keep.
	 *
	 * @param string[]|string $emails
	 */
	public function spamComplaint(array|string $emails): void;

	/**
	 * Signals that the recipient opened a message in the given section (a sign of activity).
	 *
	 * This only dispatches an event; the manager keeps no open state itself. The inactivity plugin
	 * uses it to reset its counter for that section.
	 *
	 * @param string[]|string $emails
	 */
	public function emailOpened(array|string $emails, string $section): void;

	/**
	 * Filters the registry in place before sending, removing recipients that must not be contacted
	 * (suspended or unsubscribed), and dispatches an event so plugins can apply their own rules.
	 */
	public function beforeEmailSent(EmailRegistry $registry, string $section, string $category = SectionCategory::Global): void;

	/**
	 * Records that a message was sent to the registry's recipients and dispatches an event.
	 *
	 * The inactivity plugin uses it to increment its counter for sections that enabled it.
	 */
	public function afterEmailSent(EmailRegistry $registry, string $section, string $category = SectionCategory::Global): void;

	/**
	 * Checks whether the recipient may be contacted in the given category (not suspended and subscribed).
	 */
	public function canSend(string $email, string $section, string $category = SectionCategory::Global): bool;

	/**
	 * Clears all tracked state for the recipient: suspensions, soft bounces and subscriptions.
	 *
	 * Dispatches an event so plugins can drop their own per-recipient state as well.
	 *
	 * @param string[]|string $emails
	 */
	public function reset(array|string $emails): void;

	/**
	 * Unsubscribes the recipient from the section's global category for inactivity and dispatches an
	 * {@see \WebChemistry\Emails\Event\InactiveEmailsEvent}. Normally called by the inactivity plugin
	 * once its counter is exceeded, but it can also be invoked directly.
	 *
	 * @param string[]|string $emails
	 */
	public function inactive(array|string $emails, string $section): void;

	/**
	 * Runs every registered periodic cleaner (e.g. plugins purging stale rows).
	 */
	public function cleanup(): void;

}
