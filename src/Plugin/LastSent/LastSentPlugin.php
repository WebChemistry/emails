<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Plugin\LastSent;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use WebChemistry\Emails\Common\PlatformQueryHelper;
use WebChemistry\Emails\Connection\ConnectionAccessor;
use WebChemistry\Emails\Event\AfterEmailSentEvent;
use WebChemistry\Emails\Event\BeforeEmailSentEvent;
use WebChemistry\Emails\Model\ManipulationModel;

final class LastSentPlugin implements EventSubscriberInterface
{

	use ManipulationModel;

	public function __construct(
		private ConnectionAccessor $connectionAccessor,
		private string $relativeFormat,
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
		$connection = $this->connectionAccessor->get();

		$now = $this->clock?->now() ?? new DateTimeImmutable();
		$date = $now->modify('-' . $this->relativeFormat)->format('Y-m-d H:i:s');

		$results = $connection->createQueryBuilder()
			->select('email, sent_at')
			->from('email_last_sent')
			->where('sent_at > :date')
			->setParameter('date', $date)
			->executeQuery();

		while ($row = $results->fetchAssociative()) {
			$event->registry->remove($row['email']);
		}
	}

	public function afterEmailSent(AfterEmailSentEvent $event): void
	{
		$fn = PlatformQueryHelper::updateColumns(['sent_at']);

		$now = ($this->clock?->now() ?? new DateTimeImmutable())->format('Y-m-d H:i:s');

		$this->insert('email_last_sent', array_map(static fn (string $email) => [
			'email' => $email,
			'sent_at' => $now,
		], $event->registry->getEmails()), 'email', $fn);
	}

	public function cleanup(): int
	{
		$connection = $this->connectionAccessor->get();

		$now = $this->clock?->now() ?? new DateTimeImmutable();
		$date = $now->modify('-' . $this->relativeFormat)->format('Y-m-d H:i:s');

		return $connection->createQueryBuilder()
			->delete('email_last_sent')
			->where('sent_at <= :date')
			->setParameter('date', $date)
			->executeStatement();
	}

}
