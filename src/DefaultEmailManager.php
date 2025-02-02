<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use WebChemistry\Emails\Event\AfterEmailSentEvent;
use WebChemistry\Emails\Event\BeforeEmailSentEvent;
use WebChemistry\Emails\Event\InactiveEmailsEvent;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriptionModel;
use WebChemistry\Emails\Model\SuspensionModel;
use WebChemistry\Emails\Section\SectionCategory;
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
		private ?EventDispatcherInterface $dispatcher = null,
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
	public function emailOpened(array|string $emails, string $section): void
	{
		$this->inactivityModel->resetCounter($emails, $this->sections->getSection($section));
	}

	/**
	 * @param string[]|string $emails
	 */
	public function inactive(array|string $emails, string $section): void
	{
		$emails = is_string($emails) ? [$emails] : $emails;

		if (!$emails) {
			return;
		}

		$section = $this->sections->getSection($section);

		$this->subscriptionModel->unsubscribe(
			$emails,
			UnsubscribeType::Inactivity,
			$section->getGlobalCategory(),
		);

		$this->dispatcher?->dispatch(new InactiveEmailsEvent($emails, $section));
	}

	public function beforeEmailSent(EmailRegistry $registry, string $section, string $category = SectionCategory::Global): void
	{
		$event = new BeforeEmailSentEvent($this, $registry, $this->sections->getCategory($section, $category));

		$this->suspensionModel->beforeEmailSent($event);
		$this->subscriptionModel->beforeEmailSent($event);

		$this->dispatcher?->dispatch($event);
	}

	public function afterEmailSent(EmailRegistry $registry, string $section, string $category = SectionCategory::Global): void
	{
		$event = new AfterEmailSentEvent($this, $registry, $this->sections->getCategory($section, $category));

		$this->inactivityModel->afterEmailSent($event);

		if ($this->dispatcher) {
			$this->dispatcher->dispatch($event);
		}
	}

	public function canSend(string $email, string $section, string $category = EmailManager::GlobalCategory): bool
	{
		$category = $this->sections->getCategory($section, $category);

		return !$this->suspensionModel->isSuspended($email, $category->section) && $this->subscriptionModel->isSubscribed($email, $category);
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
