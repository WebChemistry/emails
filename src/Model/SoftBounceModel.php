<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Model;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Throwable;
use WebChemistry\Emails\Connection\ConnectionAccessor;

final readonly class SoftBounceModel
{

	public function __construct(
		private ConnectionAccessor $connectionAccessor,
		private int $bounceLimit = 3,
	)
	{
	}

	/**
	 * @return string[]
	 */
	public function incrementBounce(string $email): array
	{
		$connection = $this->connectionAccessor->get();
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

				if (isset($exception)) {
					throw $exception;
				}

				return [$email];
			}

			$builder = $this->connectionAccessor->get()->createQueryBuilder();

			if ($counter === 1) {
				$builder->insert('email_bounce_counters')
					->values([
						'email' => '?',
						'counter' => 1,
					])
					->setParameter(0, $email);
			} else {
				$builder->update('email_bounce_counters')
					->set('counter', 'counter + 1')
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

		return [];
	}

	/**
	 * @param string|string[] $emails
	 */
	public function resetBounce(string|array $emails): void
	{
		$emails = is_string($emails) ? [$emails] : $emails;

		if (!$emails) {
			return;
		}

		$this->connectionAccessor->get()->createQueryBuilder()
			->delete('email_bounce_counters')
			->where('email IN(?)')
			->setParameter(0, $emails, ArrayParameterType::STRING)
			->executeStatement();
	}

	public function getBounceCount(string $email): int
	{
		return $this->_getBounceCount($email);
	}

	private function _getBounceCount(string $email, bool $lock = false): int
	{
		$query = $this->connectionAccessor->get()->createQueryBuilder();
		$query->from('email_bounce_counters', 'c');
		$query->select('c.counter');
		$query->where('c.email = :email');
		$query->setParameter('email', $email);

		if ($lock && !$this->connectionAccessor->get()->getDatabasePlatform() instanceof SqlitePlatform) {
			$query->forUpdate();
		}

		$values = $query->fetchFirstColumn();
		$key = array_key_first($values);

		return $key === null ? 0 : (int) $values[$key];
	}

}
