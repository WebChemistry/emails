<?php declare(strict_types = 1);

namespace Tests\Confirmation;

use Tests\TestCase;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Confirmation\ConfirmationManager;

final class ConfirmationManagerTest extends TestCase
{

	public function testConfirmationCode(): void
	{
		$encoder = new ConfirmationManager(new Encoder(openssl_random_pseudo_bytes(32)));

		$this->assertSame($this->firstEmail, $encoder->verify($encoder->getConfirmationCode($this->firstEmail)));
	}

}
