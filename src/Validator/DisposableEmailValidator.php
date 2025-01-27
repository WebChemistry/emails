<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Validator;

use WebChemistry\DisposableEmails\EmailChecker;
use WebChemistry\DisposableEmails\Provider\BuiltinProvider;

final readonly class DisposableEmailValidator implements EmailValidator
{

	public const Code = 'DisposableEmail';

	private EmailChecker $checker;

	public function __construct(?EmailChecker $checker = null)
	{
		$this->checker = $checker ?? $this->createChecker();
	}

	/**
	 * @param mixed[] $options
	 */
	public function validate(string $email, array $options = []): ValidationResult
	{
		$ok = $this->checker->isValid($email);

		return new ValidationResult($ok, $ok ? 'OK' : self::Code, self::class);
	}

	private function createChecker(): EmailChecker
	{
		$checker = new EmailChecker();
		$checker->addProvider(new BuiltinProvider());
		$checker->setValueIfMissingAt(false);

		return $checker;
	}

}
