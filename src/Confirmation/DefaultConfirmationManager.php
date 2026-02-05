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

	public function getConfirmationCode(string $email): string
	{
		return $this->encoder->withType(EncodeType::Encrypted)->encode($email);
	}

	public function verify(string $code): ?string
	{
		$values = $this->encoder->withType(EncodeType::Encrypted)->decode($code);

		return $values[0] ?? null;
	}

}
