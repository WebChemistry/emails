<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Token;

use Doctrine\DBAL\Connection;
use Symfony\Component\Clock\DatePoint;

final readonly class DefaultTokenRepository implements TokenRepository
{

	public function __construct(
		private Connection $connection,
		private string $tableName = 'tokens',
		private string $idColumn = 'id',
		private string $tokenColumn = 'token',
		private string $createdColumn = 'created',
	)
	{
	}

	public function getToken(string $id): ?string
	{
		$stmt = $this->connection->createQueryBuilder()
			->from($this->tableName)
			->select($this->tokenColumn)
			->where(sprintf('%s = :id', $this->idColumn))
			->setParameter('id', $id)
			->setMaxResults(1)
			->executeQuery();

		$token = $stmt->fetchFirstColumn()[0] ?? null;

		return is_string($token) ? $token : null;
	}

	public function upsert(string $id, string $token): void
	{
		$isTokenInDb = $this->isTokenInDatabase($id);

		if ($isTokenInDb) {
			$this->connection->createQueryBuilder()
				->update($this->tableName)
				->set($this->tokenColumn, ':token')
				->set($this->createdColumn, ':created')
				->where(sprintf('%s = :id', $this->idColumn))
				->setParameter('token', $token)
				->setParameter('created', (new DatePoint())->format('Y-m-d H:i:s'))
				->setParameter('id', $id)
				->executeStatement();

			return;
		}

		$this->connection->insert($this->tableName, [
			$this->idColumn => $id,
			$this->tokenColumn => $token,
			$this->createdColumn => (new DatePoint())->format('Y-m-d H:i:s'),
		]);
	}

	private function isTokenInDatabase(string $id): bool
	{
		$stmt = $this->connection->createQueryBuilder()
			->from($this->tableName, 'e')
			->select(sprintf('COUNT(e.%s)', $this->tokenColumn))
			->where(sprintf('e.%s = :id', $this->idColumn))
			->setParameter('id', $id)
			->setMaxResults(1)
			->executeQuery();

		return $stmt->fetchFirstColumn()[0] > 0;
	}

}
