<?php declare(strict_types = 1);

namespace Tests\Confirmation;

use Tests\TestCase;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Confirmation\DefaultConfirmationManager;

final class ConfirmationManagerTest extends TestCase
{

	public function testConfirmationCode(): void
	{
		$encoder = new DefaultConfirmationManager(new Encoder(openssl_random_pseudo_bytes(32)));

		$this->assertSame($this->firstEmail, $encoder->verify($encoder->getConfirmationCode($this->firstEmail)));
	}

	public function testInvalidConfirmationCode(): void
	{
		$encoder = new DefaultConfirmationManager(new Encoder(openssl_random_pseudo_bytes(32)));

		$this->assertNull($encoder->verify(substr($encoder->getConfirmationCode($this->firstEmail), 0, -1)));
	}

}
