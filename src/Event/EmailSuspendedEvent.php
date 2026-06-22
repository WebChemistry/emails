<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Event;

final readonly class EmailSuspendedEvent
{

	/**
	 * @param string[] $emails
	 */
	public function __construct(
		public array $emails,
	)
	{
	}

}
