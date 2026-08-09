<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

use Psr\EventDispatcher\EventDispatcherInterface;
use WebChemistry\Emails\Cleanup\PeriodicCleaner;
use WebChemistry\Emails\Event\AfterEmailSentEvent;
use WebChemistry\Emails\Event\BeforeEmailSentEvent;
use WebChemistry\Emails\Event\EmailForgottenEvent;
use WebChemistry\Emails\Event\EmailMovedEvent;
use WebChemistry\Emails\Event\EmailOpenedEvent;
use WebChemistry\Emails\Event\EmailResetEvent;
use WebChemistry\Emails\Event\EmailResubscribedEvent;
use WebChemistry\Emails\Event\EmailSuspendedEvent;
use WebChemistry\Emails\Event\InactiveEmailsEvent;
use WebChemistry\Emails\Link\DecodedResubscribeValue;
use WebChemistry\Emails\Link\DecodedUnsubscribeValue;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriptionModel;
use WebChemistry\Emails\Model\SuspensionModel;
use WebChemistry\Emails\Section\DefaultSectionSubscriptionMap;
use WebChemistry\Emails\Section\SectionCategory;
use WebChemistry\Emails\Section\Sections;
use WebChemistry\Emails\Section\SectionSubscriptionMap;
use WebChemistry\Emails\Type\SuspensionType;
use WebChemistry\Emails\Type\UnsubscribeType;

final readonly class DefaultEmailManager implements EmailManager
{

	/**
	 * @param iterable<PeriodicCleaner> $cleaners
	 */
	public function __construct(
		private Sections $sections,
		private SoftBounceModel $softBounceModel,
		private SubscriptionModel $subscriptionModel,
		private SuspensionModel $suspensionModel,
		private ?EventDispatcherInterface $dispatcher = null,
		private iterable $cleaners = [],
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

		$this->suspended($emails);
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
		return new DefaultSectionSubscriptionMap($this->subscriptionModel, $this->sections->getSection($section), $email);
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

		$this->dispatcher?->dispatch(new EmailResubscribedEvent($email, $category->section));
	}

	public function softBounce(string $email): void
	{
		$unsubscribed = $this->softBounceModel->incrementBounce($email);

		if ($unsubscribed) {
			$this->suspensionModel->suspend($unsubscribed, SuspensionType::SoftBounce);

			$this->suspended($unsubscribed);
		}
	}

	/**
	 * @param string[]|string $emails
	 */
	public function spamComplaint(array|string $emails): void
	{
		$this->suspensionModel->suspend($emails, SuspensionType::SpamComplaint);

		$this->suspended($emails);
	}

	/**
	 * @param string[]|string $emails
	 */
	public function emailOpened(array|string $emails, string $section): void
	{
		$this->dispatcher?->dispatch(
			new EmailOpenedEvent(is_string($emails) ? [$emails] : $emails, $this->sections->getSection($section)),
		);
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

		$this->dispatcher?->dispatch($event);
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
		$this->softBounceModel->resetBounce($emails);

		$this->subscriptionModel->reset($emails);
		$this->suspensionModel->reset($emails);

		$this->dispatcher?->dispatch(new EmailResetEvent(is_string($emails) ? [$emails] : $emails));
	}

	/**
	 * @param string[]|string $emails
	 */
	public function forget(array|string $emails): void
	{
		$this->softBounceModel->resetBounce($emails);

		$this->subscriptionModel->reset($emails);
		$this->suspensionModel->reset($emails);

		$this->dispatcher?->dispatch(new EmailForgottenEvent(is_string($emails) ? [$emails] : $emails));
	}

	public function move(string $from, string $to): void
	{
		if ($from === $to) {
			return;
		}

		$this->softBounceModel->move($from, $to);
		$this->subscriptionModel->move($from, $to);
		$this->suspensionModel->move($from, $to);

		$this->dispatcher?->dispatch(new EmailMovedEvent($from, $to));
	}

	/**
	 * @param string[]|string $emails
	 */
	private function suspended(array|string $emails): void
	{
		$this->softBounceModel->resetBounce($emails);

		$this->dispatcher?->dispatch(new EmailSuspendedEvent(is_string($emails) ? [$emails] : $emails));
	}

	public function cleanup(): void
	{
		foreach ($this->cleaners as $cleaner) {
			$cleaner->cleanup($this->getSections());
		}
	}

}
