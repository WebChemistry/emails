<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Event;

final readonly class EmailResetEvent
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
