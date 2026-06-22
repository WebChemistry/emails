<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Event;

use WebChemistry\Emails\Section\Section;

final readonly class EmailResubscribedEvent
{

	public function __construct(
		public string $email,
		public Section $section,
	)
	{
	}

}
