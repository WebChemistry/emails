<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Event;

final readonly class EmailMovedEvent
{

	public function __construct(
		public string $from,
		public string $to,
	)
	{
	}

}
