<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Event;

use WebChemistry\Emails\Section\Section;

final readonly class EmailOpenedEvent
{

	/**
	 * @param string[] $emails
	 */
	public function __construct(
		public array $emails,
		public Section $section,
	)
	{
	}

}
