<?php declare(strict_types = 1);

namespace Tests;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use LogicException;

final class ManagerRegistry implements \Doctrine\Persistence\ManagerRegistry
{

	public function __construct(
		private Connection $connection,
	)
	{
	}

	public function getDefaultConnectionName(): string
	{
		throw new LogicException('Not implemented.');
	}

	public function getConnection(?string $name = null): object
	{
		return $this->connection;
	}

	public function getConnections(): array
	{
		throw new LogicException('Not implemented.');
	}

	public function getConnectionNames(): array
	{
		throw new LogicException('Not implemented.');
	}

	public function getDefaultManagerName(): string
	{
		throw new LogicException('Not implemented.');
	}

	public function getManager(?string $name = null): ObjectManager
	{
		throw new LogicException('Not implemented.');
	}

	public function getManagers(): array
	{
		throw new LogicException('Not implemented.');
	}

	public function resetManager(?string $name = null): ObjectManager
	{
		throw new LogicException('Not implemented.');
	}

	public function getManagerNames(): array
	{
		throw new LogicException('Not implemented.');
	}

	public function getRepository(string $persistentObject, ?string $persistentManagerName = null): ObjectRepository
	{
		throw new LogicException('Not implemented.');
	}

	public function getManagerForClass(string $class): ObjectManager|null
	{
		throw new LogicException('Not implemented.');
	}

}
