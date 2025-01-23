<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

readonly class EmailAccount
{

	public function __construct(
		public string $email,
		public ?string $name = null,
	)
	{
	}

	public function toString(): string
	{
		if ($this->name === null || $this->name === '') {
			return $this->email;
		}

		return sprintf('%s <%s>', $this->name, $this->email);
	}

}
