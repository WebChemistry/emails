<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Confirmation;

use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Common\EncodeType;

interface ConfirmationManager
{

	public function getConfirmationCode(string $email): string;

	public function verify(string $code): ?string;

}
