<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

final readonly class SubscriberEmailAccount extends EmailAccount
{

	/**
	 * @param array<non-empty-string, scalar|null> $fields
	 * @param array<non-empty-string, mixed> $options
	 */
	public function __construct(
		string $email,
		?string $name = null,
		public array $fields = [],
		public array $options = [],
	)
	{
		parent::__construct($email, $name);
	}

}
