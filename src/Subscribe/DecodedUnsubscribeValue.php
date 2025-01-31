<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Subscribe;

final readonly class DecodedUnsubscribeValue
{

	/**
	 * @param list<string|null> $arguments
	 */
	public function __construct(
		public string $email,
		public ?string $section = null,
		public array $arguments = [],
	)
	{
	}

}
