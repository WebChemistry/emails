<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

use LogicException;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriberModel;
use WebChemistry\Emails\Subscribe\DecodedResubscribeValue;
use WebChemistry\Emails\Subscribe\DecodedUnsubscribeValue;
use WebChemistry\Emails\Subscribe\SubscribeManager;

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
		private ?SubscribeManager $subscribeManager = null,
	)
	{
	}

	/**
	 * @param string[]|string $emails
	 */
	public function hardBounce(array|string $emails): void
	{
		$this->subscriberModel->unsubscribe($emails, self::SuspensionTypeHardBounce);
		$this->reset($emails);
	}

	/**
	 * @param string[]|string $emails
	 */
	public function unsubscribe(array|string $emails, string $section): void
	{
		$this->subscriberModel->unsubscribe($emails, self::SuspensionTypeUnsubscribe, $section);
	}

	public function processSubscriptionLink(string $link): void
	{
		if (!$this->subscribeManager) {
			throw new LogicException('SubscribeManager is not set.');
		}

		$value = $this->subscribeManager->loadQueryParameter($link);

		if ($value instanceof DecodedUnsubscribeValue) {
			$this->unsubscribe([$value->email], $value->section ?? self::SectionGlobal);
		} else if ($value instanceof DecodedResubscribeValue) {
			$this->resubscribe($value->email, $value->section ?? self::SectionGlobal);
		}
	}

	public function resubscribe(string $email, string $section): void
	{
		$this->subscriberModel->resubscribe($email, section: $section);
	}

	public function softBounce(string $email): void
	{
		$unsubscribed = $this->softBounceModel->incrementBounce($email);

		if ($unsubscribed) {
			$this->reset($unsubscribed);
		}
	}

	/**
	 * @param string[]|string $emails
	 */
	public function spamComplaint(array|string $emails): void
	{
		$this->subscriberModel->unsubscribe($emails, self::SuspensionTypeSpamComplaint);

		$this->reset($emails);
	}

	/**
	 * @param string[]|string $emails
	 */
	public function recordOpenActivity(array|string $emails, string $section): void
	{
		$this->inactivityModel->resetCounter($emails, $section);
	}

	/**
	 * @param string[]|string $emails
	 */
	public function recordSentActivity(array|string $emails, string $section): void
	{
		$unsubscribed = $this->inactivityModel->incrementCounter($emails, $section);

		if ($unsubscribed) {
			$this->reset($unsubscribed);
		}
	}

	/**
	 * @return string[]
	 */
	public function getSuspensionReasons(string $email, string $section): array
	{
		return $this->subscriberModel->getReasons($email, $section);
	}

	public function isSuspended(string $email, string $section): bool
	{
		return $this->subscriberModel->isSuspended($email, $section);
	}

	/**
	 * @param string[] $emails
	 * @return string[]
	 */
	public function clearFromSuspended(array $emails, string $section): array
	{
		return $this->subscriberModel->clearFromSuspended($emails, $section);
	}

	/**
	 * @param string[]|string $emails
	 */
	private function reset(array|string $emails): void
	{
		$this->softBounceModel->resetBounce($emails);
		$this->inactivityModel->resetAllCounterSections($emails);
	}

}
