<?php declare(strict_types = 1);

namespace Tests\Common;

use LogicException;
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

	public function testTrailingNullArgumentsAreTrimmed(): void
	{
		$token = $this->encoder->encode($this->firstEmail, null, null);

		$this->assertSame([$this->firstEmail], $this->encoder->decode($token));
	}

	public function testNullArgumentInMiddleIsPreserved(): void
	{
		$token = $this->encoder->encode($this->firstEmail, null, 'x');

		$this->assertSame([$this->firstEmail, null, 'x'], $this->encoder->decode($token));
	}

}
