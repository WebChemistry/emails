<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Plugin\Inactivity;

use Doctrine\DBAL\ArrayParameterType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WebChemistry\Emails\Connection\ConnectionAccessor;
use WebChemistry\Emails\Event\AfterEmailSentEvent;
use WebChemistry\Emails\Event\EmailForgottenEvent;
use WebChemistry\Emails\Event\EmailOpenedEvent;
use WebChemistry\Emails\Event\EmailResetEvent;
use WebChemistry\Emails\Event\EmailResubscribedEvent;
use WebChemistry\Emails\Event\EmailSuspendedEvent;
use WebChemistry\Emails\Model\ManipulationModel;
use WebChemistry\Emails\Section\Section;

final class InactivityPlugin implements EventSubscriberInterface
{

	use ManipulationModel;

	public function __construct(
		private ConnectionAccessor $connectionAccessor,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			AfterEmailSentEvent::class => 'afterEmailSent',
			EmailOpenedEvent::class => 'emailOpened',
			EmailResubscribedEvent::class => 'emailResubscribed',
			EmailSuspendedEvent::class => 'emailSuspended',
			EmailResetEvent::class => 'emailReset',
			EmailForgottenEvent::class => 'emailForgotten',
		];
	}

	public function afterEmailSent(AfterEmailSentEvent $event): void
	{
		$config = $event->category->section->configCollection->getOrNull(InactivityConfig::class);

		if ($config === null) {
			return;
		}

		$unsubscribed = $this->incrementCounter($event->registry->getEmails(), $event->category->section, $config->limit);

		$event->emailManager->inactive($unsubscribed, $event->category->section->name);
	}

	public function emailOpened(EmailOpenedEvent $event): void
	{
		$this->resetCounter($event->emails, $event->section);
	}

	public function emailResubscribed(EmailResubscribedEvent $event): void
	{
		$this->resetCounter($event->email, $event->section);
	}

	public function emailSuspended(EmailSuspendedEvent $event): void
	{
		$this->resetAllCounterSections($event->emails);
	}

	public function emailReset(EmailResetEvent $event): void
	{
		$this->resetAllCounterSections($event->emails);
	}

	public function emailForgotten(EmailForgottenEvent $event): void
	{
		$this->resetAllCounterSections($event->emails);
	}

	/**
	 * @param string|string[] $emails
	 * @return string[]
	 */
	public function incrementCounter(string|array $emails, Section $section, int $limit): array
	{
		$emails = is_string($emails) ? [$emails] : $emails;

		if (!$emails) {
			return [];
		}

		$this->insert(
			'email_inactivity_counters',
			array_map(fn (string $email) => ['email' => $email, 'section' => $section->name, 'counter' => 1], $emails),
			['email', 'section'],
			fn () => 'counter = counter + 1',
		);

		$this->resetCounter($inactiveEmails = $this->getInactiveEmails($section->name, $limit), $section);

		return $inactiveEmails;
	}

	public function getCount(string $email, Section $section): int
	{
		return (int) $this->connectionAccessor->get()->fetchOne(
			'SELECT counter FROM email_inactivity_counters WHERE email = ? AND section = ?',
			[$email, $section->name],
		);
	}

	/**
	 * @param string|string[] $emails
	 */
	public function resetAllCounterSections(string|array $emails): void
	{
		$emails = is_string($emails) ? [$emails] : $emails;

		if (!$emails) {
			return;
		}

		$this->connectionAccessor->get()->createQueryBuilder()
			->delete('email_inactivity_counters')
			->where('email IN(?)')
			->setParameter(0, $emails, ArrayParameterType::STRING)
			->executeStatement();
	}

	/**
	 * @param string|string[] $emails
	 */
	public function resetCounter(string|array $emails, Section $section): void
	{
		$emails = is_string($emails) ? [$emails] : $emails;

		if (!$emails) {
			return;
		}

		$this->connectionAccessor->get()->createQueryBuilder()
			->delete('email_inactivity_counters')
			->where('email IN(?)')
			->andWhere('section = ?')
			->setParameter(0, $emails, ArrayParameterType::STRING)
			->setParameter(1, $section->name)
			->executeStatement();
	}

	/**
	 * @return string[]
	 */
	private function getInactiveEmails(string $section, int $limit): array
	{
		$connection = $this->connectionAccessor->get();

		$result = $connection->createQueryBuilder()
			->select('email')
			->from('email_inactivity_counters')
			->where('counter > :counter')
			->andWhere('section = :section')
			->setParameter('counter', $limit)
			->setParameter('section', $section)
			->executeQuery();

		$emails = [];

		while (($row = $result->fetchOne()) !== false) {
			$emails[] = $row;
		}

		return $emails;
	}

}
