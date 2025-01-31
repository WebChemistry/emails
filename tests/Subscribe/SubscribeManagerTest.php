<?php declare(strict_types = 1);

namespace Tests\Subscribe;

use InvalidArgumentException;
use Tests\TestCase;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Common\EncodeType;
use WebChemistry\Emails\Subscribe\DecodedResubscribeValue;
use WebChemistry\Emails\Subscribe\DecodedUnsubscribeValue;
use WebChemistry\Emails\Subscribe\SubscribeManager;

final class SubscribeManagerTest extends TestCase
{

	private SubscribeManager $manager;

	protected function setUp(): void
	{
		$encoder = new Encoder('secret', EncodeType::Basic);
		$this->manager = new SubscribeManager($encoder);
	}

	public function testUnsubscribeLink(): void
	{
		$hash = 'u=v1.b.00aa103ab72126599c49578b792075c1b3eb71354162c78ea881cd9ce402835bdGVzdEBleGFtcGxlLmNvbQ';
		$expected = 'http://example.com/unsubscribe?' . $hash;

		$this->assertSame(
			$expected,
			$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe', $this->firstEmail),
		);
		$this->assertSame(
			$expected,
			$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe?', $this->firstEmail),
		);

		$expected = 'http://example.com/unsubscribe?id=12&' . $hash;

		$this->assertSame(
			$expected,
			$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe?id=12', $this->firstEmail),
		);
		$this->assertSame(
			$expected,
			$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe?id=12&', $this->firstEmail),
		);
	}

	public function testResubscribeLink(): void
	{
		$hash = 'r=v1.b.00aa103ab72126599c49578b792075c1b3eb71354162c78ea881cd9ce402835bdGVzdEBleGFtcGxlLmNvbQ';
		$expected = 'http://example.com/unsubscribe?' . $hash;

		$this->assertSame(
			$expected,
			$this->manager->addResubscribeQueryParameter('http://example.com/unsubscribe', $this->firstEmail),
		);
		$this->assertSame(
			$expected,
			$this->manager->addResubscribeQueryParameter('http://example.com/unsubscribe?', $this->firstEmail),
		);

		$expected = 'http://example.com/unsubscribe?id=12&' . $hash;

		$this->assertSame(
			$expected,
			$this->manager->addResubscribeQueryParameter('http://example.com/unsubscribe?id=12', $this->firstEmail),
		);
		$this->assertSame(
			$expected,
			$this->manager->addResubscribeQueryParameter('http://example.com/unsubscribe?id=12&', $this->firstEmail),
		);
	}

	public function testLinkGenerationWithSection(): void
	{
		$this->assertSame(
			$expected = 'http://example.com/unsubscribe?u=v1.b.d449b1b40491bbb871755a52c8a576353ac313c501209b18f24c649df3557c7fdGVzdEBleGFtcGxlLmNvbQ.c2VjdGlvbg',
			$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe', $this->firstEmail, 'section'),
		);

		$this->assertEquals(new DecodedUnsubscribeValue(
			$this->firstEmail,
			'section',
		), $this->manager->loadUnsubscribeQueryParameter($expected));
	}

	public function testResubscribeLoadWithParameters(): void
	{
		$this->assertSame(
			$expected = 'http://example.com/unsubscribe?r=v1.b.d449b1b40491bbb871755a52c8a576353ac313c501209b18f24c649df3557c7fdGVzdEBleGFtcGxlLmNvbQ.c2VjdGlvbg',
			$this->manager->addResubscribeQueryParameter('http://example.com/unsubscribe', $this->firstEmail, 'section'),
		);

		$this->assertEquals(new DecodedResubscribeValue(
			$this->firstEmail,
			'section',
		), $this->manager->loadResubscribeQueryParameter($expected));
	}

	public function testLinkGenerationWithSectionAndNulls(): void
	{
		$this->assertSame(
			$expected = 'http://example.com/unsubscribe?u=v1.b.f3c4560b1f879b5a0caca0febd572fe2c516b23faa9baf04134e22c4b005dd72dGVzdEBleGFtcGxlLmNvbQ.c2VjdGlvbg..Zm9v',
			$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe', $this->firstEmail, 'section', null, 'foo'),
		);

		$this->assertEquals(new DecodedUnsubscribeValue(
			$this->firstEmail,
			'section',
			[null, 'foo'],
		), $this->manager->loadUnsubscribeQueryParameter($expected));
	}

	public function testParameterAlreadyExists(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe?u=foo', $this->firstEmail);
	}

	public function testParameterAlreadyExists2(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe?id=42&u=foo', $this->firstEmail);
	}

	public function testGetting(): void
	{
		$link = 'http://example.com/unsubscribe?u=v1.b.00aa103ab72126599c49578b792075c1b3eb71354162c78ea881cd9ce402835bdGVzdEBleGFtcGxlLmNvbQ';
		$this->assertEquals(new DecodedUnsubscribeValue(
			$this->firstEmail,
		), $this->manager->loadUnsubscribeQueryParameter($link));

		$this->assertNull($this->manager->loadUnsubscribeQueryParameter('http://example.com/unsubscribe'));
		$this->assertNull($this->manager->loadUnsubscribeQueryParameter('http://example.com/unsubscribe?u=foo'));
		$this->assertNull($this->manager->loadUnsubscribeQueryParameter(substr($link, 0, -1)));
	}

}
