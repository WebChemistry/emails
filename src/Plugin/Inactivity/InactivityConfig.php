<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Plugin\Inactivity;

final readonly class InactivityConfig
{

	public function __construct(
		public int $limit,
	)
	{
	}

}
