<?php declare(strict_types = 1);

namespace Tests\Link;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\SectionEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Common\EncodeType;
use WebChemistry\Emails\Link\BaseUrlSubscribeLinkGenerator;
use WebChemistry\Emails\Link\DecodedResubscribeValue;
use WebChemistry\Emails\Link\DecodedUnsubscribeValue;
use WebChemistry\Emails\Section\Section;
use WebChemistry\Emails\Section\SectionCategory;

final class BaseUrlSubscribeLinkGeneratorTest extends TestCase
{

	use SectionEnvironment;

	private const HashToExpect = 'v1.b.0b9df7821514fafc2dd3ec85b912c30fcd2599ad3bb13155496a19a303ee2194dGVzdEBleGFtcGxlLmNvbQ.bm90aWZpY2F0aW9ucw';
	private const CategoryHashToExpect = 'v1.b.ab50c6a053366d0431424f4149408565be4d6cac7f381a3cdfde3173eef761dbdGVzdEBleGFtcGxlLmNvbQ.bm90aWZpY2F0aW9ucw.YXJ0aWNsZQ';

	private BaseUrlSubscribeLinkGenerator $linkGenerator;

	private Encoder $encoder;

	protected function setUp(): void
	{
		$this->encoder = new Encoder('secret', EncodeType::Basic);
		$this->linkGenerator = $this->create('http://example.com');
	}

	#[TestWith(['http://example.com/unsubscribe', 'http://example.com/unsubscribe?'])]
	#[TestWith(['http://example.com/unsubscribe', 'http://example.com/unsubscribe?', 'article', self::CategoryHashToExpect])]
	#[TestWith(['http://example.com/unsubscribe?', 'http://example.com/unsubscribe?'])]
	#[TestWith(['http://example.com/unsubscribe?id=12', 'http://example.com/unsubscribe?id=12&'])]
	#[TestWith(['http://example.com/unsubscribe?id=12&', 'http://example.com/unsubscribe?id=12&'])]
	public function testUnsubscribe(string $baseUrl, string $expectedBaseUrl, string $category = SectionCategory::Global,string $hashToExpect = self::HashToExpect): void
	{
		$linkGenerator = $this->create($baseUrl);

		$link = $linkGenerator->unsubscribe($this->firstEmail, 'notifications', $category);

		$this->assertNotNull($link);

		$this->assertSame($expectedBaseUrl . 'u=' . $hashToExpect, $link);
	}

	#[TestWith(['http://example.com/resubscribe', 'http://example.com/resubscribe?'])]
	#[TestWith(['http://example.com/resubscribe', 'http://example.com/resubscribe?', 'article', self::CategoryHashToExpect])]
	#[TestWith(['http://example.com/resubscribe?', 'http://example.com/resubscribe?'])]
	#[TestWith(['http://example.com/resubscribe?id=12', 'http://example.com/resubscribe?id=12&'])]
	#[TestWith(['http://example.com/resubscribe?id=12&', 'http://example.com/resubscribe?id=12&'])]
	public function testResubscribe(string $baseUrl, string $expectedBaseUrl, string $category = SectionCategory::Global, string $hashToExpect = self::HashToExpect): void
	{
		$linkGenerator = $this->create($baseUrl);

		$link = $linkGenerator->resubscribe($this->firstEmail, 'notifications', $category);

		$this->assertNotNull($link);

		$this->assertSame($expectedBaseUrl . 'r=' . $hashToExpect, $link);
	}

	private function create(string $baseUrl): BaseUrlSubscribeLinkGenerator
	{
		return new BaseUrlSubscribeLinkGenerator($baseUrl, $this->sections, $this->encoder);
	}

	public function testUnsubscribeAllCategories(): void
	{
		$link = $this->linkGenerator->unsubscribe($this->firstEmail, 'section', 'category');

		$this->assertNotNull($link);

		$this->assertEquals(new DecodedUnsubscribeValue($this->firstEmail, 'section', SectionCategory::Global), $this->linkGenerator->load($link));
	}

	#[TestWith([SectionCategory::Global])]
	#[TestWith(['article'])]
	public function testLoadUnsubscribe(string $category): void
	{
		$link = $this->linkGenerator->unsubscribe($this->firstEmail, 'notifications', $category);

		$this->assertNotNull($link);

		$this->assertEquals(new DecodedUnsubscribeValue($this->firstEmail, 'notifications', $category), $this->linkGenerator->load($link));
	}

	public function testUnsubscribeNotUnsubscribable(): void
	{
		$this->assertNull($this->linkGenerator->unsubscribe($this->firstEmail, Section::Essential));
	}

	#[TestWith([SectionCategory::Global])]
	#[TestWith(['article'])]
	public function testLoadResubscribe(string $category): void
	{
		$link = $this->linkGenerator->resubscribe($this->firstEmail, 'notifications', $category);

		$this->assertNotNull($link);
		$this->assertEquals(new DecodedResubscribeValue($this->firstEmail, 'notifications', $category), $this->linkGenerator->load($link));
	}

	public function testResubscribeNotUnsubscribable(): void
	{
		$this->assertNull($this->linkGenerator->resubscribe($this->firstEmail, Section::Essential));
	}

}
