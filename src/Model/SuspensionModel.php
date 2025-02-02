<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Model;

use Doctrine\DBAL\ArrayParameterType;
use WebChemistry\Emails\Connection\ConnectionAccessor;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\EmailRegistry;
use WebChemistry\Emails\Event\BeforeEmailSentEvent;
use WebChemistry\Emails\Section\Section;
use WebChemistry\Emails\Section\SectionCategory;
use WebChemistry\Emails\Type\SuspensionType;

final readonly class SuspensionModel
{

	use ManipulationModel;

	public function __construct(
		private ConnectionAccessor $connectionAccessor,
	)
	{
	}

	public function beforeEmailSent(BeforeEmailSentEvent $event): void
	{
		if ($event->registry->isEmpty()) {
			return;
		}

		foreach ($this->getSuspendedEmails($event->registry->getEmails(), $event->category->section) as $email) {
			$event->registry->remove($email);
		}
	}

	/**
	 * @param string[] $emails
	 * @return iterable<string>
	 */
	private function getSuspendedEmails(array $emails, Section $section): iterable
	{
		$connection = $this->connectionAccessor->get();

		$qb = $connection->createQueryBuilder()
			->select('email')
			->from('email_suspensions')
			->where('email IN(:emails)')
			->setParameter('emails', $emails, ArrayParameterType::STRING);

		if ($section->isEssential()) {
			$qb->andWhere('type = :type')
				->setParameter('type', SuspensionType::HardBounce->value);
		}

		$result = $qb->executeQuery();

		while ($value = $result->fetchAssociative()) {
			yield $value['email'];
		}
	}

	public function isSuspended(string $email, Section $section): bool
	{
		$qb = $this->connectionAccessor->get()->createQueryBuilder()
			->select('1')
			->from('email_suspensions')
			->where('email = :email')
			->setParameter('email', $email);

		if ($section->isEssential()) {
			$qb->andWhere('type = :type')
				->setParameter('type', SuspensionType::HardBounce->value);
		}

		return (bool) $qb->executeQuery()->fetchOne();
	}

	/**
	 * @param string|string[] $emails
	 */
	public function suspend(string|array $emails, SuspensionType $type): void
	{
		$now = date('Y-m-d H:i:s');

		$this->insert(
			'email_suspensions',
			array_map(fn (string $email) => ['email' => $email, 'type' => $type->value, 'created_at' => $now], is_string($emails) ? [$emails] : $emails),
			['email', 'type'],
		);
	}

	/**
	 * @return SuspensionType[]
	 */
	public function getReasons(string $email): array
	{
		$connection = $this->connectionAccessor->get();

		$result = $connection->createQueryBuilder()
			->select('type')
			->from('email_suspensions')
			->where('email = :email')
			->setParameter('email', $email)
			->executeQuery();

		$reasons = [];

		while (($row = $result->fetchOne()) !== false) {
			$reasons[] = SuspensionType::from($row);
		}

		return $reasons;
	}

	/**
	 * Removes hard and soft bounces. If you want to remove all types (hard bounces, soft bounces and spam complaints) use clear().
	 *
	 * @param string|string[] $emails
	 */
	public function activate(array|string $emails, bool $includeHardBounces = true): void
	{
		$types = [SuspensionType::SoftBounce->value];

		if ($includeHardBounces) {
			$types[] = SuspensionType::HardBounce->value;
		}

		$this->connectionAccessor->get()->createQueryBuilder()
			->delete('email_suspensions')
			->where('email IN(:emails)')
			->andWhere('type IN(:types)')
			->setParameter('emails', is_string($emails) ? [$emails] : $emails, ArrayParameterType::STRING)
			->setParameter('types', $types, ArrayParameterType::STRING)
			->executeStatement();
	}

	/**
	 * @param string[]|string $emails
	 */
	public function reset(array|string $emails): void
	{
		$this->connectionAccessor->get()->createQueryBuilder()
			->delete('email_suspensions')
			->where('email IN(:emails)')
			->setParameter('emails', is_string($emails) ? [$emails] : $emails, ArrayParameterType::STRING)
			->executeStatement();
	}

}
