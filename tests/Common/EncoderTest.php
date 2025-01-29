<?php declare(strict_types = 1);

namespace Tests\Common;

use Tests\TestCase;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Common\EncodeType;

final class EncoderTest extends TestCase
{

	private Encoder $encoder;

	protected function setUp(): void
	{
		$this->encoder = new Encoder('secret', EncodeType::Basic);
	}

	public function testEncodeDecode(): void
	{
		$this->assertSame(
			[$this->firstEmail],
			$this->encoder->decode($this->encoder->encode($this->firstEmail)),
		);
		$this->assertSame(
			[$this->firstEmail, 'section'],
			$this->encoder->decode($this->encoder->encode($this->firstEmail, 'section')),
		);
		$this->assertSame(
			[$this->firstEmail, 'section', 'arg1'],
			$this->encoder->decode($this->encoder->encode($this->firstEmail, 'section', 'arg1')),
		);
		$this->assertSame(
			[$this->firstEmail, 'section', 'arg1', 'arg2'],
			$this->encoder->decode($this->encoder->encode($this->firstEmail, 'section', 'arg1', 'arg2')),
		);

		$this->assertSame(116, strlen($this->encoder->encode($this->firstEmail, 'section', 'arg1', 'arg2')));
		$this->assertSame($this->encoder->encode($this->firstEmail, 'section'), $this->encoder->encode($this->firstEmail, 'section'));
	}

	public function testSalted(): void
	{
		$this->encoder = $this->encoder->withType(EncodeType::Salt);

		$this->assertEquals(
			[$this->firstEmail],
			$this->encoder->decode($this->encoder->encode($this->firstEmail)),
		);
		$this->assertEquals(
			[$this->firstEmail, 'section'],
			$this->encoder->decode($this->encoder->encode($this->firstEmail, 'section')),
		);
		$this->assertEquals(
			[$this->firstEmail, 'section', 'arg1'],
			$this->encoder->decode($this->encoder->encode($this->firstEmail, 'section', 'arg1')),
		);
		$this->assertEquals(
			[$this->firstEmail, 'section', 'arg1', 'arg2'],
			$this->encoder->decode($this->encoder->encode($this->firstEmail, 'section', 'arg1', 'arg2')),
		);

		$this->assertNotSame([
			$this->encoder->encode($this->firstEmail),
			$this->encoder->encode($this->firstEmail),
			$this->encoder->encode($this->firstEmail),
		], [
			$this->encoder->encode($this->firstEmail),
			$this->encoder->encode($this->firstEmail),
			$this->encoder->encode($this->firstEmail),
		]);
	}

	public function testEncrypted(): void
	{
		$this->encoder = new Encoder(openssl_random_pseudo_bytes(32), EncodeType::Encrypted);

		$this->assertEquals(
			[$this->firstEmail],
			$this->encoder->decode($this->encoder->encode($this->firstEmail)),
		);
	}

}
