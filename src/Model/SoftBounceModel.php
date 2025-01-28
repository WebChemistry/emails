<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Model;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Persistence\ConnectionRegistry;
use Throwable;
use WebChemistry\Emails\EmailManager;

final readonly class SoftBounceModel
{

	public function __construct(
		private ConnectionRegistry $registry,
		private SubscriberModel $subscriberModel,
		private int $bounceLimit = 3,
	)
	{
	}

	public function incrementBounce(string $email): void
	{
		$connection = $this->getConnection();
		$connection->beginTransaction();
		$commited = false;

		try {
			$counter = $this->_getBounceCount($email, true) + 1;

			if ($counter >= $this->bounceLimit) {
				$connection->commit();
				$commited = true;

				try {
					$this->resetBounce($email);
				} catch (Throwable $exception) {
				}

				$this->subscriberModel->unsubscribe($email, EmailManager::SuspensionTypeSoftBounce);

				if (isset($exception)) {
					throw $exception;
				}

				return;
			}

			$builder = $this->getConnection()->createQueryBuilder();

			if ($counter === 1) {
				$builder->insert('email_bounce_counters')
					->values([
						'email' => '?',
						'bounce_count' => 1,
					])
					->setParameter(0, $email);
			} else {
				$builder->update('email_bounce_counters')
					->set('bounce_count', 'bounce_count + 1')
					->where('email = ?')
					->setParameter(0, $email);
			}

			$builder->executeStatement();

			$connection->commit();
		} catch (Throwable $exception) {
			if (!$commited) {
				$connection->rollBack();
			}

			throw $exception;
		}
	}

	public function resetBounce(string $email): void
	{
		$stmt = $this->getConnection()->prepare(
			'DELETE FROM email_bounce_counters WHERE email = ?'
		);
		$stmt->bindValue(1, $email);

		$stmt->executeStatement();
	}

	public function getBounceCount(string $email): int
	{
		return $this->_getBounceCount($email);
	}

	private function _getBounceCount(string $email, bool $lock = false): int
	{
		$query = $this->getConnection()->createQueryBuilder();
		$query->from('email_bounce_counters', 'c');
		$query->select('c.bounce_count');
		$query->where('c.email = :email');
		$query->setParameter('email', $email);

		if ($lock && !$this->getConnection()->getDatabasePlatform() instanceof SqlitePlatform) {
			$query->forUpdate();
		}

		$values = $query->fetchFirstColumn();
		$key = array_key_first($values);

		return $key === null ? 0 : (int) $values[$key];
	}

	private function getConnection(): Connection
	{
		$connection = $this->registry->getConnection();

		assert($connection instanceof Connection);

		return $connection;
	}

}
