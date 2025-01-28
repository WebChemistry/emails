<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriberModel;

final class EmailManager
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

	public function __construct(
		private InactivityModel $inactivityModel,
		private SubscriberModel $subscriberModel,
		private SoftBounceModel $softBounceModel,
	)
	{
	}

	/**
	 * @param string[]|string $emails
	 */
	public function hardBounce(array|string $emails): void
	{
		$this->subscriberModel->unsubscribe($emails, self::SuspensionTypeHardBounce);
	}

	/**
	 * @param string[]|string $emails
	 */
	public function unsubscribe(array|string $emails, string $section = self::SectionGlobal): void
	{
		$this->subscriberModel->unsubscribe($emails, self::SuspensionTypeUnsubscribe, $section);
	}

	public function resubscribe(string $email, string $section = self::SectionGlobal): void
	{
		$this->subscriberModel->resubscribe($email, section: $section);
	}

	public function softBounce(string $email): void
	{
		$this->softBounceModel->incrementBounce($email);
	}

	/**
	 * @param string[]|string $emails
	 */
	public function spamComplaint(array|string $emails): void
	{
		$this->subscriberModel->unsubscribe($emails, self::SuspensionTypeSpamComplaint);
	}

	/**
	 * @param string[]|string $emails
	 */
	public function recordOpenActivity(array|string $emails, string $section = self::SectionGlobal): void
	{
		$this->inactivityModel->resetCounter($emails, $section);
	}

	/**
	 * @param string[]|string $emails
	 */
	public function recordSentActivity(array|string $emails, string $section = self::SectionGlobal): void
	{
		$this->inactivityModel->incrementCounter($emails, $section);
	}

}
