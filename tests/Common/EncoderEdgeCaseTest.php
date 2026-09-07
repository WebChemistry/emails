<?php declare(strict_types = 1);

namespace Tests\Common;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Common\EncodeType;

final class EncoderEdgeCaseTest extends TestCase
{

	private Encoder $encoder;

	protected function setUp(): void
	{
		$this->encoder = new Encoder('secret', EncodeType::Basic);
	}

	public function testDecodeTooFewPartsReturnsNull(): void
	{
		$this->assertNull($this->encoder->decode(''));
		$this->assertNull($this->encoder->decode('v1'));
		$this->assertNull($this->encoder->decode('v1.basic'));
	}

	public function testDecodeWrongVersionReturnsNull(): void
	{
		$token = $this->encoder->encode($this->firstEmail);
		$parts = explode('.', $token);
		$parts[0] = 'v2';

		$this->assertNull($this->encoder->decode(implode('.', $parts)));
	}

	public function testDecodeUnknownTypeReturnsNull(): void
	{
		$token = $this->encoder->encode($this->firstEmail);
		$parts = explode('.', $token);
		$parts[1] = 'bogus';

		$this->assertNull($this->encoder->decode(implode('.', $parts)));
	}

	public function testDecodeTamperedPayloadReturnsNull(): void
	{
		$token = $this->encoder->encode($this->firstEmail, 'section');
		$parts = explode('.', $token);
		$lastIndex = count($parts) - 1;
		$last = $parts[$lastIndex];
		$parts[$lastIndex] = ($last[0] === 'A' ? 'B' : 'A') . substr($last, 1);

		$this->assertNull($this->encoder->decode(implode('.', $parts)));
	}

	public function testDecodeWithDifferentSecretReturnsNull(): void
	{
		$token = $this->encoder->encode($this->firstEmail);
		$otherEncoder = new Encoder('other-secret', EncodeType::Basic);

		$this->assertNull($otherEncoder->decode($token));
	}

	public function testFromBase64Roundtrip(): void
	{
		$encoder = Encoder::fromBase64(base64_encode('secret'), EncodeType::Basic);

		$this->assertSame([$this->firstEmail], $encoder->decode($encoder->encode($this->firstEmail)));
	}

	public function testFromBase64WithInvalidSecretThrows(): void
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('Invalid secret.');

		Encoder::fromBase64('!!!not base64!!!');
	}

	public function testEncryptedDecodeWithMissingIvReturnsNull(): void
	{
		$encoder = new Encoder(str_repeat('k', 32), EncodeType::Encrypted);
		$token = $encoder->encode('secret-value');
		$parts = explode('.', $token);
		array_pop($parts);

		$this->assertNull($encoder->decode(implode('.', $parts)));
	}

	public function testEncryptedDecodeWithTamperedCiphertextReturnsNull(): void
	{
		$encoder = new Encoder(str_repeat('k', 32), EncodeType::Encrypted);
		$token = $encoder->encode($this->firstEmail, 'argument');
		$parts = explode('.', $token);

		// Segments: version, type, hash + value, the argument, iv. Flip the first character of the argument.
		$argument = $parts[3];
		$parts[3] = ($argument[0] === 'A' ? 'B' : 'A') . substr($argument, 1);

		$this->assertNull($encoder->decode(implode('.', $parts)));
	}

	public function testEncryptedDecodeWithCorruptedIvReturnsNull(): void
	{
		$encoder = new Encoder(str_repeat('k', 32), EncodeType::Encrypted);
		$token = $encoder->encode($this->firstEmail, 'argument');

		// The iv is the last segment; growing it must be rejected outright rather than truncated by openssl.
		$this->assertNull($encoder->decode(sprintf('%s-tampered', $token)));
		$this->assertNull($encoder->decode(sprintf('%sAAAA', $token)));
	}

	public function testEncryptedDecodeWithDifferentSecretReturnsNull(): void
	{
		$encoder = new Encoder(str_repeat('k', 32), EncodeType::Encrypted);
		$otherEncoder = new Encoder(str_repeat('o', 32), EncodeType::Encrypted);

		// Runs a few tokens: a wrong key usually fails to decrypt, and the rare one that happens to decrypt into
		// garbage with valid padding is caught by the signature - both must end as null, never an exception.
		for ($i = 0; $i < 20; $i++) {
			$this->assertNull($otherEncoder->decode($encoder->encode($this->firstEmail, 'argument')));
		}
	}

	#[DataProvider('provideEncodeTypes')]
	public function testTrailingNullArgumentsAreTrimmed(EncodeType $type): void
	{
		$encoder = new Encoder(str_repeat('k', 32), $type);

		$plain = $encoder->encode($this->firstEmail);
		$withNulls = $encoder->encode($this->firstEmail, null, null);

		// Dropped before the salt or iv is appended, so an omitted argument never adds an empty segment.
		$this->assertSame(substr_count($plain, '.'), substr_count($withNulls, '.'));
		$this->assertSame([$this->firstEmail], $encoder->decode($withNulls));
		$this->assertSame([$this->firstEmail, 'section'], $encoder->decode($encoder->encode($this->firstEmail, 'section', null)));
	}

	#[DataProvider('provideEncodeTypes')]
	public function testTokenWithExplicitEmptyTrailingSegmentStillDecodes(EncodeType $type): void
	{
		// Tokens issued before trimming worked for salted and encrypted types carry an empty segment per
		// trailing null; they decode to the same values as before, with the null in place.
		$encoder = new Encoder(str_repeat('k', 32), $type);
		$token = $encoder->encode($this->firstEmail, 'section', null, 'x');

		$this->assertSame([$this->firstEmail, 'section', null, 'x'], $encoder->decode($token));
	}

	/**
	 * @return iterable<string, array{EncodeType}>
	 */
	public static function provideEncodeTypes(): iterable
	{
		yield 'basic' => [EncodeType::Basic];
		yield 'salt' => [EncodeType::Salt];
		yield 'encrypted' => [EncodeType::Encrypted];
	}

	public function testNullArgumentInMiddleIsPreserved(): void
	{
		$token = $this->encoder->encode($this->firstEmail, null, 'x');

		$this->assertSame([$this->firstEmail, null, 'x'], $this->encoder->decode($token));
	}

}
