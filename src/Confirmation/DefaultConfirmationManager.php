<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Confirmation;

use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Common\EncodeType;

final class DefaultConfirmationManager implements ConfirmationManager
{

	public function __construct(
		private Encoder $encoder,
	)
	{
	}

	public function getConfirmationCode(string $email, ?string ...$arguments): string
	{
		return $this->encoder->withType(EncodeType::Encrypted)->encode($email, ...$arguments);
	}

	public function verify(string $code): ?string
	{
		return $this->decode($code)?->email;
	}

	public function decode(string $code): ?DecodedConfirmationValue
	{
		$values = $this->encoder->withType(EncodeType::Encrypted)->decode($code);

		if ($values === null) {
			return null;
		}

		$email = $values[0];

		if ($email === null) {
			return null;
		}

		return new DecodedConfirmationValue($email, array_slice($values, 1));
	}

}
