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
		$hash = 'u=v1.b.0b9df7821514fafc2dd3ec85b912c30fcd2599ad3bb13155496a19a303ee2194dGVzdEBleGFtcGxlLmNvbQ.bm90aWZpY2F0aW9ucw';
		$expected = 'http://example.com/unsubscribe?' . $hash;

		$this->assertSame(
			$expected,
			$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe', $this->firstEmail, 'notifications'),
		);
		$this->assertSame(
			$expected,
			$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe?', $this->firstEmail, 'notifications'),
		);

		$expected = 'http://example.com/unsubscribe?id=12&' . $hash;

		$this->assertSame(
			$expected,
			$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe?id=12', $this->firstEmail, 'notifications'),
		);
		$this->assertSame(
			$expected,
			$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe?id=12&', $this->firstEmail, 'notifications'),
		);
	}

	public function testResubscribeLink(): void
	{
		$hash = 'r=v1.b.0b9df7821514fafc2dd3ec85b912c30fcd2599ad3bb13155496a19a303ee2194dGVzdEBleGFtcGxlLmNvbQ.bm90aWZpY2F0aW9ucw';
		$expected = 'http://example.com/unsubscribe?' . $hash;

		$this->assertSame(
			$expected,
			$this->manager->addResubscribeQueryParameter('http://example.com/unsubscribe', $this->firstEmail, 'notifications'),
		);
		$this->assertSame(
			$expected,
			$this->manager->addResubscribeQueryParameter('http://example.com/unsubscribe?', $this->firstEmail, 'notifications'),
		);

		$expected = 'http://example.com/unsubscribe?id=12&' . $hash;

		$this->assertSame(
			$expected,
			$this->manager->addResubscribeQueryParameter('http://example.com/unsubscribe?id=12', $this->firstEmail, 'notifications'),
		);
		$this->assertSame(
			$expected,
			$this->manager->addResubscribeQueryParameter('http://example.com/unsubscribe?id=12&', $this->firstEmail, 'notifications'),
		);
	}

	public function testLinkGenerationWithCategory(): void
	{
		$this->assertSame(
			$expected = 'http://example.com/unsubscribe?u=v1.b.ab50c6a053366d0431424f4149408565be4d6cac7f381a3cdfde3173eef761dbdGVzdEBleGFtcGxlLmNvbQ.bm90aWZpY2F0aW9ucw.YXJ0aWNsZQ',
			$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe', $this->firstEmail, 'notifications', 'article'),
		);

		$this->assertEquals(new DecodedUnsubscribeValue(
			$this->firstEmail,
			'notifications',
			'article',
		), $this->manager->loadUnsubscribeQueryParameter($expected));
	}

	public function testResubscribeLoadWithParameters(): void
	{
		$this->assertSame(
			$expected = 'http://example.com/unsubscribe?r=v1.b.ab50c6a053366d0431424f4149408565be4d6cac7f381a3cdfde3173eef761dbdGVzdEBleGFtcGxlLmNvbQ.bm90aWZpY2F0aW9ucw.YXJ0aWNsZQ',
			$this->manager->addResubscribeQueryParameter('http://example.com/unsubscribe', $this->firstEmail, 'notifications', 'article'),
		);

		$this->assertEquals(new DecodedResubscribeValue(
			$this->firstEmail,
			'notifications',
			'article',
		), $this->manager->loadResubscribeQueryParameter($expected));
	}

	public function testLinkGenerationWithSectionAndNulls(): void
	{
		$this->assertSame(
			$expected = 'http://example.com/unsubscribe?u=v1.b.1ea0f86ce537f05aa4a251f12ef3b2d81e65ce442623e720b124555276a584afdGVzdEBleGFtcGxlLmNvbQ.bm90aWZpY2F0aW9ucw.YXJ0aWNsZQ..Zm9v',
			$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe', $this->firstEmail, 'notifications', 'article', null, 'foo'),
		);

		$this->assertEquals(new DecodedUnsubscribeValue(
			$this->firstEmail,
			'notifications',
			'article',
			[null, 'foo'],
		), $this->manager->loadUnsubscribeQueryParameter($expected));
	}

	public function testParameterAlreadyExists(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe?u=foo', $this->firstEmail, 'notifications');
	}

	public function testParameterAlreadyExists2(): void
	{
		$this->expectException(InvalidArgumentException::class);

		$this->manager->addUnsubscribeQueryParameter('http://example.com/unsubscribe?id=42&u=foo', $this->firstEmail, 'notifications');
	}

	public function testGetting(): void
	{
		$link = 'http://example.com/unsubscribe?u=v1.b.0b9df7821514fafc2dd3ec85b912c30fcd2599ad3bb13155496a19a303ee2194dGVzdEBleGFtcGxlLmNvbQ.bm90aWZpY2F0aW9ucw';
		$this->assertEquals(new DecodedUnsubscribeValue(
			$this->firstEmail,
			'notifications',
		), $this->manager->loadUnsubscribeQueryParameter($link));

		$this->assertNull($this->manager->loadUnsubscribeQueryParameter('http://example.com/unsubscribe'));
		$this->assertNull($this->manager->loadUnsubscribeQueryParameter('http://example.com/unsubscribe?u=foo'));
		$this->assertNull($this->manager->loadUnsubscribeQueryParameter(substr($link, 0, -1)));
	}

}
