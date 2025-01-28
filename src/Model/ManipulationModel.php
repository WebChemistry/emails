<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Model;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use LogicException;

trait ManipulationModel
{

	use ConnectionModel;

	/**
	 * @param array<array<string, string|int>> $values
	 * @param string|non-empty-list<string> $conflictColumns
	 * @param (callable(string $platform): string)|null $onUpdate
	 */
	private function insert(string $table, array $values, string|array $conflictColumns, ?callable $onUpdate = null): void
	{
		if (!$values) {
			return;
		}

		$connection = $this->getConnection();
		$platform = $connection->getDatabasePlatform();
		$conflictColumns = is_string($conflictColumns) ? [$conflictColumns] : $conflictColumns;

		[$expression, $columns, $parameters] = $this->buildValuesExpression($values);

		if ($platform instanceof SqlitePlatform) {
			$stmt = $connection->prepare(sprintf(
				'INSERT INTO %s (%s) VALUES %s ON CONFLICT(%s) DO %s',
				$table,
				implode(', ', $columns),
				$expression,
				implode(', ', $conflictColumns),
				$onUpdate ? sprintf('UPDATE SET %s', $onUpdate('sqlite')) : 'NOTHING',
			));
		} else if ($platform instanceof MySQLPlatform) {
			if ($onUpdate) {
				$onUpdateExpression = $onUpdate('mysql');
			} else {
				$onUpdateExpression = sprintf('%s = %s', $conflictColumns[0], $conflictColumns[0]);
			}

			$stmt = $connection->prepare(sprintf(
				'INSERT INTO %s (%s) VALUES %s ON DUPLICATE KEY UPDATE %s',
				$table,
				implode(', ', $columns),
				$expression,
				$onUpdateExpression,
			));
		} else {
			throw new LogicException('Unsupported platform.');
		}

		foreach ($parameters as $index => $val) {
			$stmt->bindValue($index + 1, $val);
		}

		$stmt->executeStatement();
	}

	/**
	 * @param array<array<string, string|int>> $rows
	 * @return array{string, list<string>, list<string|int>}
	 */
	private function buildValuesExpression(array $rows): array
	{
		$expression = '';
		$parameters = [];

		$required = [];

		foreach ($rows as $values) {
			if (!$values) {
				throw new LogicException('Values cannot be empty.');
			}

			if (!$required) {
				$required = array_keys($values);
			} else if ($required !== array_keys($values)) {
				throw new LogicException('All rows must have the same keys.');
			}

			$expression .= '(';
			foreach ($values as $value) {
				$parameters[] = $value;
				$expression .= '?, ';
			}
			$expression = substr($expression, 0, -2) . '), ';
		}

		return [substr($expression, 0, -2), $required, $parameters];
	}

}
