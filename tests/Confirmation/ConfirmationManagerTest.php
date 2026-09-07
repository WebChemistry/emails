<?php declare(strict_types = 1);

namespace Tests\Confirmation;

use Tests\TestCase;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Common\EncodeType;
use WebChemistry\Emails\Confirmation\DecodedConfirmationValue;
use WebChemistry\Emails\Confirmation\DefaultConfirmationManager;

final class ConfirmationManagerTest extends TestCase
{

	private string $secret;

	private DefaultConfirmationManager $manager;

	protected function setUp(): void
	{
		$this->secret = openssl_random_pseudo_bytes(32);
		$this->manager = new DefaultConfirmationManager(new Encoder($this->secret));
	}

	public function testConfirmationCode(): void
	{
		$this->assertSame($this->firstEmail, $this->manager->verify($this->manager->getConfirmationCode($this->firstEmail)));
	}

	public function testInvalidConfirmationCode(): void
	{
		$this->assertNull($this->manager->verify(substr($this->manager->getConfirmationCode($this->firstEmail), 0, -1)));
	}

	public function testDecodeWithoutArguments(): void
	{
		$decoded = $this->manager->decode($this->manager->getConfirmationCode($this->firstEmail));

		$this->assertEquals(new DecodedConfirmationValue($this->firstEmail), $decoded);
	}

	public function testDecodeCarriesArguments(): void
	{
		$code = $this->manager->getConfirmationCode($this->firstEmail, '/creators/@jane?tab=posts', null, 'sign-up');

		$this->assertEquals(
			new DecodedConfirmationValue($this->firstEmail, ['/creators/@jane?tab=posts', null, 'sign-up']),
			$this->manager->decode($code),
		);
	}

	public function testVerifyIgnoresArguments(): void
	{
		$this->assertSame(
			$this->firstEmail,
			$this->manager->verify($this->manager->getConfirmationCode($this->firstEmail, '/creators/@jane')),
		);
	}

	public function testArgumentsAreNotReadableFromTheCode(): void
	{
		$returnPath = '/creators/@jane';
		$code = $this->manager->getConfirmationCode($this->firstEmail, $returnPath);

		$this->assertStringNotContainsString($returnPath, $code);
		$this->assertStringNotContainsString(rtrim(strtr(base64_encode($returnPath), '+/', '-_'), '='), $code);
	}

	public function testTrailingNullArgumentsKeepTheArgumentlessFormat(): void
	{
		$plain = $this->manager->getConfirmationCode($this->firstEmail);
		$withNulls = $this->manager->getConfirmationCode($this->firstEmail, null, null);

		$this->assertSame(substr_count($plain, '.'), substr_count($withNulls, '.'));
		$this->assertEquals(new DecodedConfirmationValue($this->firstEmail), $this->manager->decode($withNulls));
	}

	public function testDecodesCodeIssuedBeforeArgumentsExisted(): void
	{
		// The pre-arguments code was the encrypted email alone; a link like that may still sit in an inbox.
		$legacyCode = (new Encoder($this->secret, EncodeType::Encrypted))->encode($this->firstEmail);

		$this->assertEquals(new DecodedConfirmationValue($this->firstEmail), $this->manager->decode($legacyCode));
		$this->assertSame($this->firstEmail, $this->manager->verify($legacyCode));
	}

	public function testTamperedArgumentIsRejected(): void
	{
		$code = $this->manager->getConfirmationCode($this->firstEmail, '/creators/@jane');
		$parts = explode('.', $code);

		// Segments: version, type, hash + email, the argument, iv.
		$argument = $parts[3];
		$parts[3] = ($argument[0] === 'A' ? 'B' : 'A') . substr($argument, 1);

		$this->assertNull($this->manager->decode(implode('.', $parts)));
		$this->assertNull($this->manager->verify(implode('.', $parts)));
	}

	public function testDroppedArgumentIsRejected(): void
	{
		$code = $this->manager->getConfirmationCode($this->firstEmail, '/creators/@jane');
		$parts = explode('.', $code);
		unset($parts[3]);

		$this->assertNull($this->manager->decode(implode('.', $parts)));
	}

	public function testCodeFromAnotherSecretIsRejected(): void
	{
		$other = new DefaultConfirmationManager(new Encoder(openssl_random_pseudo_bytes(32)));

		$this->assertNull($other->decode($this->manager->getConfirmationCode($this->firstEmail, '/creators/@jane')));
		$this->assertNull($other->verify($this->manager->getConfirmationCode($this->firstEmail)));
	}

	public function testMalformedCodeIsRejected(): void
	{
		$this->assertNull($this->manager->decode(''));
		$this->assertNull($this->manager->decode('v1.encrypted'));
		$this->assertNull($this->manager->decode('not a code at all'));
	}

}
