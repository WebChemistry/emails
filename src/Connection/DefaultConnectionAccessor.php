<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ConnectionRegistry;

final readonly class DefaultConnectionAccessor implements ConnectionAccessor
{

	public function __construct(
		private ConnectionRegistry $registry,
	)
	{
	}

	public function get(): Connection
	{
		$connection = $this->registry->getConnection();

		assert($connection instanceof Connection);

		return $connection;
	}

}
