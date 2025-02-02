<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Model;

use Doctrine\DBAL\ArrayParameterType;
use WebChemistry\Emails\Connection\ConnectionAccessor;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\Type\SuspensionType;

final readonly class SuspensionModel
{

	use ManipulationModel;

	public function __construct(
		private ConnectionAccessor $connectionAccessor,
	)
	{
	}

	/**
	 * @template TKey of array-key
	 * @param array<TKey, string> $emails
	 * @return array<TKey, string>
	 */
	public function filterEmailsForDelivery(array $emails): array
	{
		$suspended = $this->createSuspensionIndex($emails);

		return array_filter($emails, static fn ($email): bool => !isset($suspended[$email]));
	}

	/**
	 * @param string[] $emails
	 * @return array<string, bool>
	 */
	public function createSuspensionIndex(array $emails): array
	{
		if (!$emails) {
			return [];
		}

		$connection = $this->connectionAccessor->get();

		$result = $connection->createQueryBuilder()
			->select('email')
			->from('email_suspensions')
			->where('email IN(:emails)')
			->setParameter('emails', $emails, ArrayParameterType::STRING)
			->executeQuery();

		$index = [];

		while (($value = $result->fetchAssociative()) !== false) {
			$index[$value['email']] = true;
		}

		return $index;
	}

	public function isSuspended(string $email): bool
	{
		return (bool) $this->connectionAccessor->get()->fetchOne(
			'SELECT 1 FROM email_suspensions WHERE email = ?',
			[$email]
		);
	}

	/**
	 * @param string[] $emails
	 * @return string[]
	 */
	public function removeSuspendedEmailsFrom(array $emails): array
	{
		$index = $this->getSuspendedEmailIndex($emails);

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
	 * @param EmailAccount[] $accounts
	 * @return EmailAccount[]
	 */
	public function removeSuspendedEmailAccountsFrom(array $accounts): array
	{
		$index = $this->getSuspendedEmailIndex(array_map(fn (EmailAccount $account) => $account->email, $accounts));

		if (!$index) {
			return $accounts;
		}

		$return = [];

		foreach ($accounts as $account) {
			if (!isset($index[$account->email])) {
				$return[] = $account;
			}
		}

		return $return;
	}

	/**
	 * @param string[] $emails
	 * @return array<string, bool>
	 */
	private function getSuspendedEmailIndex(array $emails): array
	{
		if (!$emails) {
			return [];
		}

		$connection = $this->connectionAccessor->get();

		$result = $connection->createQueryBuilder()
			->select('email')
			->from('email_suspensions')
			->where('email IN(:emails)')
			->setParameter('emails', $emails, ArrayParameterType::STRING)
			->executeQuery();

		$index = [];

		while (($value = $result->fetchAssociative()) !== false) {
			$index[$value['email']] = true;
		}

		return $index;
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
