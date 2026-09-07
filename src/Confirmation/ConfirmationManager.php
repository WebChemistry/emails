<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Confirmation;

interface ConfirmationManager
{

	/**
	 * Creates the code a confirmation link carries. The arguments travel inside the code - encrypted and covered
	 * by its signature - so whatever the caller attaches (a return path, a purpose) comes back from decode()
	 * exactly as given, or not at all. Trailing nulls are dropped, so a code without arguments keeps the format
	 * of one issued before arguments existed.
	 *
	 * @return non-empty-string
	 */
	public function getConfirmationCode(string $email, ?string ...$arguments): string;

	/**
	 * The email the code was issued for, or null when the code is malformed, tampered with or signed with a
	 * different secret. A shorthand for decode() when the arguments are of no interest.
	 */
	public function verify(string $code): ?string;

	/**
	 * The email and the arguments the code was issued with, or null when the code does not verify.
	 */
	public function decode(string $code): ?DecodedConfirmationValue;

}
