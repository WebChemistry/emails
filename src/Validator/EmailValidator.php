<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Validator;

interface EmailValidator
{

	/**
	 * @param mixed[] $options
	 */
	public function validate(string $email, array $options = []): ValidationResult;

}
