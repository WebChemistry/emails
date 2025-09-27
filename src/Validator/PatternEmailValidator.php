<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Validator;

final readonly class PatternEmailValidator implements EmailValidator
{

	public const Code = 'InvalidEmail';

	public function validate(string $email, array $options = []): ValidationResult
	{
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return new ValidationResult(false, self::Code, self::class);
		}

		return new ValidationResult(true, 'OK', self::class);
	}

}
