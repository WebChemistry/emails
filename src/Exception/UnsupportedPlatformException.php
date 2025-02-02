<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Exception;

use Exception;

final class UnsupportedPlatformException extends Exception
{

	public function __construct(string $platform)
	{
		parent::__construct(sprintf('Platform %s is not supported.', $platform));
	}

}
