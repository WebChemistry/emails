<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Validator;

final class CompoundEmailValidator implements EmailValidator
{

	/**
	 * @param EmailValidator[] $validators
	 */
	public function __construct(
		private array $validators,
	)
	{
	}

	/**
	 * @param mixed[] $options
	 */
	public function validate(string $email, array $options = []): ValidationResult
	{
		$result = new ValidationResult(true, 'OK', self::class);

		foreach ($this->validators as $validator) {
			$result = $validator->validate($email, $options);

			if (!$result->ok) {
				return $result;
			}
		}

		return $result;
	}

}
