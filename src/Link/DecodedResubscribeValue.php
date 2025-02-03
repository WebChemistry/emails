<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Link;

final readonly class DecodedResubscribeValue
{

	/**
	 * @param list<string|null> $arguments
	 */
	public function __construct(
		public string $email,
		public string $section,
		public string $category,
		public array $arguments = [],
	)
	{
	}

}
