<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

use LogicException;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriptionModel;
use WebChemistry\Emails\Model\SuspensionModel;
use WebChemistry\Emails\Subscribe\DecodedResubscribeValue;
use WebChemistry\Emails\Subscribe\DecodedUnsubscribeValue;
use WebChemistry\Emails\Subscribe\SubscribeManager;
use WebChemistry\Emails\Type\SuspensionType;
use WebChemistry\Emails\Type\UnsubscribeType;

final readonly class DefaultEmailManager implements EmailManager
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
		private SoftBounceModel $softBounceModel,
		private SubscriptionModel $subscriptionModel,
		private SuspensionModel $suspensionModel,
		private ?SubscribeManager $subscribeManager = null,
	)
	{
	}

	/**
	 * @param string[]|string $emails
	 */
	public function hardBounce(array|string $emails): void
	{
		$this->suspensionModel->suspend($emails, SuspensionType::HardBounce);

		$this->resetSoftBouncesAndInactivity($emails);
	}

	/**
	 * @param string[]|string $emails
	 */
	public function unsubscribe(array|string $emails, string $section, string $category = self::GlobalCategory): void
	{
		$emails = is_string($emails) ? [$emails] : $emails;

		foreach ($emails as $email) {
			$this->subscriptionModel->unsubscribe($email, UnsubscribeType::User, $section, $category);
		}
	}

	public function addResubscribeQueryParameter(string $link, string $email, string $section, string $category = EmailManager::GlobalCategory): string
	{
		return $this->getSubscribeManager()->addResubscribeQueryParameter($link, $email, $section, $category);
	}

	public function addUnsubscribeQueryParameter(string $link, string $email, string $section, string $category = EmailManager::GlobalCategory): string
	{
		return $this->getSubscribeManager()->addUnsubscribeQueryParameter($link, $email, $section, $category);
	}

	public function processSubscribeUnsubscribeQueryParameter(string $link): void
	{
		$value = $this->getSubscribeManager()->loadQueryParameter($link);

		if ($value instanceof DecodedUnsubscribeValue) {
			$this->unsubscribe([$value->email], $value->section ?? self::SectionGlobal);
		} else if ($value instanceof DecodedResubscribeValue) {
			$this->resubscribe($value->email, $value->section ?? self::SectionGlobal);
		}
	}

	public function resubscribe(string $email, string $section, string $category = EmailManager::GlobalCategory): void
	{
		$this->subscriptionModel->resubscribe($email, $section, $category);
	}

	public function softBounce(string $email): void
	{
		$unsubscribed = $this->softBounceModel->incrementBounce($email);

		if ($unsubscribed) {
			$this->suspensionModel->suspend($unsubscribed, SuspensionType::SoftBounce);

			$this->resetSoftBouncesAndInactivity($unsubscribed);
		}
	}

	/**
	 * @param string[]|string $emails
	 */
	public function spamComplaint(array|string $emails): void
	{
		$this->suspensionModel->suspend($emails, SuspensionType::SpamComplaint);

		$this->resetSoftBouncesAndInactivity($emails);
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
			$this->subscriptionModel->unsubscribe($unsubscribed, UnsubscribeType::Inactivity, $section);

			$this->resetSoftBouncesAndInactivity($unsubscribed);
		}
	}

	public function canSend(string $email, string $section, string $category = EmailManager::GlobalCategory): bool
	{
		return !$this->suspensionModel->isSuspended($email) && $this->subscriptionModel->isSubscribed($email, $section, $category);
	}

	/**
	 * @param string[] $emails
	 * @return string[]
	 */
	public function filterEmailsForDelivery(array $emails, string $section, string $category = EmailManager::GlobalCategory): array
	{
		return $this->_filterEmails($emails, $section, $category);
	}

	/**
	 * @param EmailAccount[] $accounts
	 * @return EmailAccount[]
	 */
	public function filterEmailAccountsForDelivery(array $accounts, string $section, string $category = EmailManager::GlobalCategory): array
	{
		return $this->_filterEmails($accounts, $section, $category, static fn (EmailAccount $account): string => $account->email);
	}

	/**
	 * @template TValue
	 * @template TKey of array-key
	 * @param array<TKey, TValue> $values
	 * @param callable(TValue): string $getEmail
	 * @return TValue[]
	 */
	private function _filterEmails(array $values, string $section, string $category, ?callable $getEmail = null): array
	{
		$emails = [];

		if ($getEmail) {
			foreach ($values as $key => $value) {
				$emails[$key] = $getEmail($value);
			}
		} else {
			$emails = $values;
		}

		$emails = $this->suspensionModel->filterEmailsForDelivery($emails);
		$emails = $this->subscriptionModel->filterEmailsForDelivery($emails, $section, $category);

		$return = [];

		foreach ($emails as $key => $_) {
			$return[] = $values[$key];
		}

		return $return;
	}

	/**
	 * @param string[]|string $emails
	 */
	public function reset(array|string $emails): void
	{
		$this->resetSoftBouncesAndInactivity($emails);

		$this->subscriptionModel->reset($emails);
		$this->suspensionModel->reset($emails);
	}

	private function getSubscribeManager(): SubscribeManager
	{
		if (!$this->subscribeManager) {
			throw new LogicException('SubscribeManager is not set.');
		}

		return $this->subscribeManager;
	}

	/**
	 * @param string[]|string $emails
	 */
	private function resetSoftBouncesAndInactivity(array|string $emails): void
	{
		$this->softBounceModel->resetBounce($emails);
		$this->inactivityModel->resetAllCounterSections($emails);
	}

}
