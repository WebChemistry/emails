<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Model;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ConnectionRegistry;
use WebChemistry\Emails\EmailManager;

final readonly class InactivityModel
{

	use ConnectionModel;
	use ManipulationModel;

	public function __construct(
		private int $maxInactivity,
		private ConnectionRegistry $registry,
		private SubscriberModel $subscriberModel,
	)
	{
	}

	/**
	 * @param string|string[] $emails
	 * @return string[]
	 */
	public function incrementCounter(string|array $emails, string $section = EmailManager::SectionGlobal): array
	{
		$emails = is_string($emails) ? [$emails] : $emails;

		if (!$emails) {
			return [];
		}

		$this->insert(
			'email_inactivity_counters',
			array_map(fn (string $email) => ['email' => $email, 'section' => $section, 'counter' => 1], $emails),
			['email', 'section'],
			fn () => 'counter = counter + 1',
		);

		$this->resetCounter($inactiveEmails = $this->getInactiveEmails($section), $section);

		$this->subscriberModel->unsubscribe($inactiveEmails, EmailManager::SuspensionTypeInactivity, $section);

		return $inactiveEmails;
	}

	public function getCount(string $email, string $section = EmailManager::SectionGlobal): int
	{
		return (int) $this->getConnection()->fetchOne(
			'SELECT counter FROM email_inactivity_counters WHERE email = ? AND section = ?',
			[$email, $section],
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

		$this->getConnection()->createQueryBuilder()
			->delete('email_inactivity_counters')
			->where('email IN(?)')
			->setParameter(0, $emails, ArrayParameterType::STRING)
			->executeStatement();
	}

	/**
	 * @param string|string[] $emails
	 */
	public function resetCounter(string|array $emails, string $section = EmailManager::SectionGlobal): void
	{
		$emails = is_string($emails) ? [$emails] : $emails;

		if (!$emails) {
			return;
		}

		$this->getConnection()->createQueryBuilder()
			->delete('email_inactivity_counters')
			->where('email IN(?)')
			->andWhere('section = ?')
			->setParameter(0, $emails, ArrayParameterType::STRING)
			->setParameter(1, $section)
			->executeStatement();
	}

	/**
	 * @return string[]
	 */
	private function getInactiveEmails(string $section): array
	{
		$connection = $this->getConnection();

		$result = $connection->createQueryBuilder()
			->select('email')
			->from('email_inactivity_counters')
			->where('counter > :counter')
			->andWhere('section = :section')
			->setParameter('counter', $this->maxInactivity)
			->setParameter('section', $section)
			->executeQuery();

		$emails = [];

		while (($row = $result->fetchOne()) !== false) {
			$emails[] = $row;
		}

		return $emails;
	}

}
