<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Plugin\LastSent;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

final readonly class LastSentConfig
{

	/**
	 * @param string $range Range in format "1 hour", "1 day", "1 week", "1 month", "1 year"
	 */
	public function __construct(
		public string $range,
	)
	{
	}

	public function getMinimum(?ClockInterface $clock = null): DateTimeImmutable
	{
		$now = $clock?->now() ?? new DateTimeImmutable();

		return $now->modify(sprintf('- %s', $this->range));
	}

}
