<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Common;

use WebChemistry\Emails\Exception\UnsupportedPlatformException;

final class PlatformQueryHelper
{

	/**
	 * @param string[] $columns
	 * @return callable(string $platform): string
	 */
	public static function updateColumns(array $columns): callable
	{
		return static function (string $platform) use ($columns): string {
			if ($platform === 'mysql') {
				return implode(', ', array_map(fn (string $column) => sprintf('%s = VALUES(%s)', $column, $column), $columns));
			}

			if ($platform === 'sqlite') {
				return implode(', ', array_map(fn (string $column) => sprintf('%s = excluded.%s', $column, $column), $columns));
			}

			throw new UnsupportedPlatformException($platform);
		};
	}

}
