<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Validator;

final readonly class ValidationResult
{

	/**
	 * @param class-string<EmailValidator> $fromClassName
	 */
	public function __construct(
		public bool $ok,
		public string $errorCode,
		public string $fromClassName,
	)
	{
	}

}
