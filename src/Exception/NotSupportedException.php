<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Exception;

use Exception;

final class NotSupportedException extends Exception
{

	public static function method(string $class, string $method): self
	{
		return new self(sprintf('Method %s is not supported by %s.', $method, $class));
	}

}
