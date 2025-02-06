<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Plugin\LastSent;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WebChemistry\Emails\Cleanup\PeriodicCleaner;
use WebChemistry\Emails\Common\PlatformQueryHelper;
use WebChemistry\Emails\Connection\ConnectionAccessor;
use WebChemistry\Emails\Event\AfterEmailSentEvent;
use WebChemistry\Emails\Event\BeforeEmailSentEvent;
use WebChemistry\Emails\Model\ManipulationModel;
use WebChemistry\Emails\Section\Sections;

final class LastSentPlugin implements EventSubscriberInterface, PeriodicCleaner
{

	use ManipulationModel;

	public function __construct(
		private ConnectionAccessor $connectionAccessor,
		private ?ClockInterface $clock = null,
	)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			BeforeEmailSentEvent::class => 'beforeEmailSent',
			AfterEmailSentEvent::class => 'afterEmailSent',
		];
	}

	public function beforeEmailSent(BeforeEmailSentEvent $event): void
	{
		/** @var LastSentConfig|null $config */
		$config = $event->category->section->configCollection->getOrNull(LastSentConfig::class);

		if (!$config) {
			return;
		}

		$connection = $this->connectionAccessor->get();

		$results = $connection->createQueryBuilder()
			->select('email')
			->from('email_last_sent')
			->where('sent_at > :date')
			->andWhere('section = :section')
			->setParameter('date', $config->getMinimum($this->clock)->format('Y-m-d H:i:s'))
			->setParameter('section', $event->category->section->name)
			->executeQuery();

		while ($row = $results->fetchAssociative()) {
			$event->registry->remove($row['email']);
		}
	}

	public function afterEmailSent(AfterEmailSentEvent $event): void
	{
		if (!$event->category->section->configCollection->has(LastSentConfig::class)) {
			return;
		}

		$fn = PlatformQueryHelper::updateColumns(['sent_at']);

		$now = ($this->clock?->now() ?? new DateTimeImmutable())->format('Y-m-d H:i:s');

		$this->insert('email_last_sent', array_map(static fn (string $email) => [
			'email' => $email,
			'sent_at' => $now,
			'section' => $event->category->section->name,
		], $event->registry->getEmails()), ['email', 'section'], $fn);
	}

	public function cleanup(Sections $sections): void
	{
		$connection = $this->connectionAccessor->get();

		foreach ($sections->getAll() as $section) {
			$config = $section->configCollection->getOrNull(LastSentConfig::class);

			if (!$config) {
				continue;
			}

			$min = $config->getMinimum($this->clock);
			$date = $min->format('Y-m-d H:i:s');

			$connection->createQueryBuilder()
				->delete('email_last_sent')
				->where('sent_at <= :date')
				->andWhere('section = :section')
				->setParameter('date', $date)
				->setParameter('section', $section->name)
				->executeStatement();
		}
	}

}
