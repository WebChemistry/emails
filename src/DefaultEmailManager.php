<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

use LogicException;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriptionModel;
use WebChemistry\Emails\Model\SuspensionModel;
use WebChemistry\Emails\Section\Sections;
use WebChemistry\Emails\Subscribe\DecodedResubscribeValue;
use WebChemistry\Emails\Subscribe\DecodedUnsubscribeValue;
use WebChemistry\Emails\Subscribe\SubscribeManager;
use WebChemistry\Emails\Type\SuspensionType;
use WebChemistry\Emails\Type\UnsubscribeType;

final readonly class DefaultEmailManager implements EmailManager
{

	public function __construct(
		private Sections $sections,
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

		$category = $this->sections->getCategory($section, $category);

		foreach ($emails as $email) {
			$this->subscriptionModel->unsubscribe($email, UnsubscribeType::User, $category);
		}
	}

	public function addResubscribeQueryParameter(string $link, string $email, string $section, string $category = EmailManager::GlobalCategory): string
	{
		$category = $this->sections->getCategory($section, $category);

		return $this->getSubscribeManager()->addResubscribeQueryParameter($link, $email, $category);
	}

	public function addUnsubscribeQueryParameter(string $link, string $email, string $section, string $category = EmailManager::GlobalCategory): string
	{
		$category = $this->sections->getCategory($section, $category);

		return $this->getSubscribeManager()->addUnsubscribeQueryParameter($link, $email, $category);
	}

	public function processSubscribeUnsubscribeQueryParameter(string $link): void
	{
		$value = $this->getSubscribeManager()->loadQueryParameter($link);

		if ($value instanceof DecodedUnsubscribeValue) {
			$this->unsubscribe([$value->email], $value->section, $value->category);
		} else if ($value instanceof DecodedResubscribeValue) {
			$this->resubscribe($value->email, $value->section, $value->category);
		}
	}

	public function resubscribe(string $email, string $section, string $category = EmailManager::GlobalCategory): void
	{
		$category = $this->sections->getCategory($section, $category);

		$this->subscriptionModel->resubscribe($email, $category);
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
		$this->inactivityModel->resetCounter($emails, $this->sections->getSection($section));
	}

	/**
	 * @param string[]|string $emails
	 */
	public function recordSentActivity(array|string $emails, string $section): void
	{
		$section = $this->sections->getSection($section);

		$unsubscribed = $this->inactivityModel->incrementCounter($emails, $section);

		if ($unsubscribed) {
			$this->subscriptionModel->unsubscribe($unsubscribed, UnsubscribeType::Inactivity, $section->getGlobalCategory());

			$this->resetSoftBouncesAndInactivity($unsubscribed);
		}
	}

	public function canSend(string $email, string $section, string $category = EmailManager::GlobalCategory): bool
	{
		$category = $this->sections->getCategory($section, $category);

		return !$this->suspensionModel->isSuspended($email) && $this->subscriptionModel->isSubscribed($email, $category);
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
		$category = $this->sections->getCategory($section, $category);

		$emails = [];

		if ($getEmail) {
			foreach ($values as $key => $value) {
				$emails[$key] = $getEmail($value);
			}
		} else {
			$emails = $values;
		}

		$emails = $this->suspensionModel->filterEmailsForDelivery($emails);
		$emails = $this->subscriptionModel->filterEmailsForDelivery($emails, $category);

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
