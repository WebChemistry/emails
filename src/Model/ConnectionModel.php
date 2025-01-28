<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Model;

use Doctrine\DBAL\Connection;

trait ConnectionModel
{

	private function getConnection(): Connection
	{
		$connection = $this->registry->getConnection();

		assert($connection instanceof Connection);

		return $connection;
	}

}
