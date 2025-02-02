<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Model;

use Doctrine\DBAL\ArrayParameterType;
use WebChemistry\Emails\Connection\ConnectionAccessor;
use WebChemistry\Emails\Section\Section;

final readonly class InactivityModel
{

	use ManipulationModel;

	public function __construct(
		private int $maxInactivity,
		private ConnectionAccessor $connectionAccessor,
	)
	{
	}

	/**
	 * @param string|string[] $emails
	 * @return string[]
	 */
	public function incrementCounter(string|array $emails, Section $section): array
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

		$this->resetCounter($inactiveEmails = $this->getInactiveEmails($section->name), $section);

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
	private function getInactiveEmails(string $section): array
	{
		$connection = $this->connectionAccessor->get();

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
