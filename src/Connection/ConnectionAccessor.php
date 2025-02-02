<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Connection;

use Doctrine\DBAL\Connection;

interface ConnectionAccessor
{

	public function get(): Connection;

}
