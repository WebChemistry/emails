<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

final readonly class EmailAccountWithFields extends EmailAccount
{

	/**
	 * @param array<string, scalar|null> $fields
	 */
	public function __construct(
		string $email,
		?string $name = null,
		public array $fields = [],
	)
	{
		parent::__construct($email, $name);
	}

}
