<?php declare(strict_types = 1);

namespace Tests\Unsubscribe;

use InvalidArgumentException;
use Tests\TestCase;
use WebChemistry\Emails\Unsubscribe\DecodedUnsubscribeValue;
use WebChemistry\Emails\Unsubscribe\UnsubscribeEncoder;
use WebChemistry\Emails\Unsubscribe\UnsubscribeManager;

final class UnsubscribeManagerTest extends TestCase
{

	private UnsubscribeManager $manager;

	protected function setUp(): void
	{
		$encoder = new UnsubscribeEncoder('secret');
		$this->manager = new UnsubscribeManager($encoder, UnsubscribeManager::NoSalt);
	}

	public function testLinkGeneration(): void
	{
		$this->assertSame(
			'http://example.com/unsubscribe?u=v1.n.a4d1fa49e4544c1d6388d1b3671b511e0a60846b4ded34017262e78039fadb7cdGVzdEBleGFtcGxlLmNvbQ',
			$this->manager->addToLink('http://example.com/unsubscribe', $this->firstEmail),
		);
		$this->assertSame(
			'http://example.com/unsubscribe?u=v1.n.a4d1fa49e4544c1d6388d1b3671b511e0a60846b4ded34017262e78039fadb7cdGVzdEBleGFtcGxlLmNvbQ',
			$this->manager->addToLink('http://example.com/unsubscribe?', $this->firstEmail),
		);

		$this->assertSame(
			'http://example.com/unsubscribe?id=12&u=v1.n.a4d1fa49e4544c1d6388d1b3671b511e0a60846b4ded34017262e78039fadb7cdGVzdEBleGFtcGxlLmNvbQ',
			$this->manager->addToLink('http://example.com/unsubscribe?id=12', $this->firstEmail),
		);
		$this->assertSame(
			'http://example.com/unsubscribe?id=12&u=v1.n.a4d1fa49e4544c1d6388d1b3671b511e0a60846b4ded34017262e78039fadb7cdGVzdEBleGFtcGxlLmNvbQ',
			$this->manager->addToLink('http://example.com/unsubscribe?id=12&', $this->firstEmail),
		);
	}

	public function testParameterAlreadyExists(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->manager->addToLink('http://example.com/unsubscribe?u=foo', $this->firstEmail);
	}

	public function testParameterAlreadyExists2(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->manager->addToLink('http://example.com/unsubscribe?id=42&u=foo', $this->firstEmail);
	}

	public function testGetting(): void
	{
		$link = 'http://example.com/unsubscribe?u=v1.n.a4d1fa49e4544c1d6388d1b3671b511e0a60846b4ded34017262e78039fadb7cdGVzdEBleGFtcGxlLmNvbQ';
		$this->assertEquals(new DecodedUnsubscribeValue(
			$this->firstEmail,
		), $this->manager->getFromLink($link));

		$this->assertNull($this->manager->getFromLink('http://example.com/unsubscribe'));
		$this->assertNull($this->manager->getFromLink('http://example.com/unsubscribe?u=foo'));
		$this->assertNull($this->manager->getFromLink(substr($link, 0, -1)));
	}

}
