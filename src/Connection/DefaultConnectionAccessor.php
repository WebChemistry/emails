<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;
use Doctrine\Persistence\ManagerRegistry;

final readonly class DefaultConnectionAccessor implements ConnectionAccessor
{

	public function __construct(
		private ManagerRegistry $managerRegistry,
		private ?string $connectionName = null,
	)
	{
	}

	public function get(): Connection
	{
		$connection = $this->managerRegistry->getConnection($this->connectionName);

		assert($connection instanceof Connection);

		return $connection;
	}

}
