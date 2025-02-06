<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

use Psr\EventDispatcher\EventDispatcherInterface;
use WebChemistry\Emails\Event\AfterEmailSentEvent;
use WebChemistry\Emails\Event\BeforeEmailSentEvent;
use WebChemistry\Emails\Event\InactiveEmailsEvent;
use WebChemistry\Emails\Link\DecodedResubscribeValue;
use WebChemistry\Emails\Link\DecodedUnsubscribeValue;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriptionModel;
use WebChemistry\Emails\Model\SuspensionModel;
use WebChemistry\Emails\Section\SectionCategory;
use WebChemistry\Emails\Section\Sections;
use WebChemistry\Emails\Section\SectionSubscriptionMap;
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
		private ?EventDispatcherInterface $dispatcher = null,
	)
	{
	}

	public function getSections(): Sections
	{
		return $this->sections;
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
	public function unsubscribe(array|string $emails, string $section, string $category = SectionCategory::Global): void
	{
		$emails = is_string($emails) ? [$emails] : $emails;

		$category = $this->sections->getCategory($section, $category);

		foreach ($emails as $email) {
			$this->subscriptionModel->unsubscribe($email, UnsubscribeType::User, $category);
		}
	}

	public function createSectionSubscriptionMap(string $section, string $email): SectionSubscriptionMap
	{
		return new SectionSubscriptionMap($this->subscriptionModel, $this->sections->getSection($section), $email);
	}

	public function processDecodedSubscribeValue(DecodedUnsubscribeValue|DecodedResubscribeValue|null $value): void
	{
		if ($value === null) {
			return;
		}

		if ($value instanceof DecodedUnsubscribeValue) {
			$this->unsubscribe([$value->email], $value->section, $value->category);
		} else {
			$this->resubscribe($value->email, $value->section, $value->category);
		}
	}

	public function resubscribe(string $email, string $section, string $category = SectionCategory::Global): void
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

	public function canSend(string $email, string $section, string $category = SectionCategory::Global): bool
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

	/**
	 * @param string[]|string $emails
	 */
	private function resetSoftBouncesAndInactivity(array|string $emails): void
	{
		$this->softBounceModel->resetBounce($emails);
		$this->inactivityModel->resetAllCounterSections($emails);
	}

}
