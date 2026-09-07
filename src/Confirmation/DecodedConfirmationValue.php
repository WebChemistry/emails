<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Confirmation;

final readonly class DecodedConfirmationValue
{

	/**
	 * @param list<string|null> $arguments
	 */
	public function __construct(
		public string $email,
		public array $arguments = [],
	)
	{
	}

}
