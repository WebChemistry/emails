<?php declare(strict_types = 1);

namespace Tests\Unsubscribe;

use Symfony\Component\Clock\MockClock;
use Tests\TestCase;
use WebChemistry\Emails\Unsubscribe\DecodedUnsubscribeValue;
use WebChemistry\Emails\Unsubscribe\UnsubscribeEncoder;

final class UnsubscribeEncoderTest extends TestCase
{

	private UnsubscribeEncoder $encoder;

	private MockClock $clock;

	protected function setUp(): void
	{
		$this->clock = new MockClock('2025-01-01 00:00:00');
		$this->encoder = new UnsubscribeEncoder('secret', $this->clock);
	}

	public function testEncodeDecode(): void
	{
		$this->assertEquals(
			new DecodedUnsubscribeValue($this->firstEmail),
			$this->encoder->decode($this->encoder->encode($this->firstEmail)),
		);
		$this->assertEquals(
			new DecodedUnsubscribeValue($this->firstEmail, 'section'),
			$this->encoder->decode($this->encoder->encode($this->firstEmail, 'section')),
		);
		$this->assertEquals(
			new DecodedUnsubscribeValue($this->firstEmail, 'section', ['arg1']),
			$this->encoder->decode($this->encoder->encode($this->firstEmail, 'section', 'arg1')),
		);
		$this->assertEquals(
			new DecodedUnsubscribeValue($this->firstEmail, 'section', ['arg1', 'arg2']),
			$this->encoder->decode($this->encoder->encode($this->firstEmail, 'section', 'arg1', 'arg2')),
		);

		$this->assertSame(116, strlen($this->encoder->encode($this->firstEmail, 'section', 'arg1', 'arg2')));
		$this->assertSame($this->encoder->encode($this->firstEmail, 'section'), $this->encoder->encode($this->firstEmail, 'section'));
	}

	public function testEncodeWithTimeSaltDecode(): void
	{
		$this->assertEquals(
			new DecodedUnsubscribeValue($this->firstEmail),
			$this->encoder->decode($this->encoder->encodeWithTimeSalt($this->firstEmail)),
		);
		$this->assertEquals(
			new DecodedUnsubscribeValue($this->firstEmail, 'section'),
			$this->encoder->decode($this->encoder->encodeWithTimeSalt($this->firstEmail, 'section')),
		);
		$this->assertEquals(
			new DecodedUnsubscribeValue($this->firstEmail, 'section', ['arg1']),
			$this->encoder->decode($this->encoder->encodeWithTimeSalt($this->firstEmail, 'section', 'arg1')),
		);
		$this->assertEquals(
			new DecodedUnsubscribeValue($this->firstEmail, 'section', ['arg1', 'arg2']),
			$this->encoder->decode($this->encoder->encodeWithTimeSalt($this->firstEmail, 'section', 'arg1', 'arg2')),
		);

		$this->assertSame($this->encoder->encodeWithTimeSalt($this->firstEmail), $previouslySalted = $this->encoder->encodeWithTimeSalt($this->firstEmail));

		$this->clock->sleep(1);

		$this->assertNotSame($this->encoder->encodeWithTimeSalt($this->firstEmail), $previouslySalted);
	}

	public function testEncodeWithSaltDecode(): void
	{
		$this->assertEquals(
			new DecodedUnsubscribeValue($this->firstEmail),
			$this->encoder->decode($this->encoder->encodeWithRandomSalt($this->firstEmail)),
		);
		$this->assertEquals(
			new DecodedUnsubscribeValue($this->firstEmail, 'section'),
			$this->encoder->decode($this->encoder->encodeWithRandomSalt($this->firstEmail, 'section')),
		);
		$this->assertEquals(
			new DecodedUnsubscribeValue($this->firstEmail, 'section', ['arg1']),
			$this->encoder->decode($this->encoder->encodeWithRandomSalt($this->firstEmail, 'section', 'arg1')),
		);
		$this->assertEquals(
			new DecodedUnsubscribeValue($this->firstEmail, 'section', ['arg1', 'arg2']),
			$this->encoder->decode($this->encoder->encodeWithRandomSalt($this->firstEmail, 'section', 'arg1', 'arg2')),
		);

		$this->assertNotSame([
			$this->encoder->encodeWithRandomSalt($this->firstEmail),
			$this->encoder->encodeWithRandomSalt($this->firstEmail),
			$this->encoder->encodeWithRandomSalt($this->firstEmail),
		], [
			$this->encoder->encodeWithRandomSalt($this->firstEmail),
			$this->encoder->encodeWithRandomSalt($this->firstEmail),
			$this->encoder->encodeWithRandomSalt($this->firstEmail),
		]);
	}

}
