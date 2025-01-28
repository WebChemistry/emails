<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Model;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ConnectionRegistry;
use InvalidArgumentException;
use WebChemistry\Emails\EmailManager;

final readonly class SubscriberModel
{

	use ConnectionModel;
	use ManipulationModel;

	public function __construct(
		private ConnectionRegistry $registry,
	)
	{
	}

	public function isSuspended(string $email, string $section = EmailManager::SectionGlobal): bool
	{
		return (bool) $this->getConnection()->fetchOne(
			'SELECT 1 FROM email_suspensions WHERE email = ? AND section IN(?)',
			[$email, $this->getSections($section)],
			[ParameterType::STRING, ArrayParameterType::STRING]
		);
	}

	/**
	 * @param string[] $emails
	 * @return string[]
	 */
	public function clearFromSuspended(array $emails, string $section = EmailManager::SectionGlobal): array
	{
		if (!$emails) {
			return [];
		}

		$connection = $this->getConnection();

		$result = $connection->createQueryBuilder()
			->select('email')
			->from('email_suspensions')
			->where('email IN(:emails)')
			->andWhere('section IN(:sections)')
			->setParameter('sections', $this->getSections($section), ArrayParameterType::STRING)
			->setParameter('emails', $emails, ArrayParameterType::STRING)
			->executeQuery();

		$index = [];

		while (($value = $result->fetchAssociative()) !== false) {
			$index[$value['email']] = true;
		}

		if (!$index) {
			return $emails;
		}

		$return = [];

		foreach ($emails as $email) {
			if (!isset($index[$email])) {
				$return[] = $email;
			}
		}

		return $return;
	}

	/**
	 * @param string|string[] $emails
	 */
	public function unsubscribe(string|array $emails, string $type, string $section = EmailManager::SectionGlobal): void
	{
		if (!in_array($type, EmailManager::SuspensionTypes, true)) {
			throw new InvalidArgumentException(
				sprintf(
					'Type %s is not supported, allowed types are %s.',
					$type,
					implode(', ', array_keys(EmailManager::SuspensionTypes)),
				)
			);
		}

		if ($section !== EmailManager::SectionGlobal && !in_array($type, EmailManager::SuspensionResubscribeTypes, true)) {
			trigger_error(sprintf('Type %s is not allowed in section %s, should be %s.', $type, $section, EmailManager::SectionGlobal), E_USER_WARNING);

			$section = EmailManager::SectionGlobal;
		}

		$now = date('Y-m-d H:i:s');

		$this->insert(
			'email_suspensions',
			array_map(fn (string $email) => ['email' => $email, 'type' => $type, 'section' => $section, 'created_at' => $now], is_string($emails) ? [$emails] : $emails),
			['email', 'type', 'section'],
		);
	}

	/**
	 * @return string[]
	 */
	public function getReasons(string $email, string $section = EmailManager::SectionGlobal): array
	{
		$connection = $this->getConnection();

		$result = $connection->createQueryBuilder()
			->select('type')
			->from('email_suspensions')
			->where('email = :email')
			->andWhere('section IN(:sections)')
			->setParameter('email', $email)
			->setParameter('sections', $this->getSections($section), ArrayParameterType::STRING)
			->executeQuery();

		$reasons = [];

		while (($row = $result->fetchOne()) !== false) {
			$reasons[$row] = $row;
		}

		return array_values($reasons);
	}

	/**
	 * @param string[] $types
	 */
	public function resubscribe(string $email, array $types = EmailManager::SuspensionResubscribeTypes, string $section = EmailManager::SectionGlobal): void
	{
		$this->getConnection()->createQueryBuilder()
			->delete('email_suspensions')
			->where('email = :email')
			->andWhere('type IN(:types)')
			->andWhere('section = :section')
			->setParameter('email', $email)
			->setParameter('section', $section)
			->setParameter('types', $types, ArrayParameterType::STRING)
			->executeStatement();

		if ($section !== EmailManager::SectionGlobal && array_diff($types, EmailManager::SuspensionResubscribeTypes)) {
			$this->getConnection()->createQueryBuilder()
				->delete('email_suspensions')
				->where('email = :email')
				->andWhere('type IN(:types)')
				->andWhere('section = :section')
				->setParameter('email', $email)
				->setParameter('section', EmailManager::SectionGlobal)
				->setParameter('types', $types, ArrayParameterType::STRING)
				->executeStatement();
		}
	}

	/**
	 * @param string $section
	 * @return string[]
	 */
	private function getSections(string $section): array
	{
		$sections = [$section];

		if ($section !== EmailManager::SectionGlobal) {
			$sections[] = EmailManager::SectionGlobal;
		}

		return $sections;
	}

}
