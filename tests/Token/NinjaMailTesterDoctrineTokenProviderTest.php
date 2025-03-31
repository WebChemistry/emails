<?php declare(strict_types = 1);

namespace Tests\Token;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tests\DatabaseEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Token\DefaultTokenRepository;
use WebChemistry\Emails\Token\NinjaMailTesterDoctrineTokenProvider;

final class NinjaMailTesterDoctrineTokenProviderTest extends TestCase
{

	use DatabaseEnvironment;

	public function testInsert(): void
	{
		$httpClient = new MockHttpClient([
			MockResponse::fromFile(__DIR__ . '/http/mail_tester_ninja_token.json'),
		]);
		$provider = $this->createProvider($httpClient);

		$this->assertSame('Mk5ETGRlQnd3WkZYOGszWXlwQV...WDhQR0pJU0tLa1R4WW5BbXRJVA==', $provider->getToken());
	}

	public function testUpsert(): void
	{
		$httpClient = new MockHttpClient([
			MockResponse::fromFile(__DIR__ . '/http/mail_tester_ninja_token.json'),
			MockResponse::fromFile(__DIR__ . '/http/mail_tester_ninja_token_second.json'),
		]);
		$provider = $this->createProvider($httpClient);

		$this->assertSame('Mk5ETGRlQnd3WkZYOGszWXlwQV...WDhQR0pJU0tLa1R4WW5BbXRJVA==', $provider->update());
		$this->assertSame('second', $provider->update());

		$this->assertSame(2, $httpClient->getRequestsCount());

		$provider->reset();

		$this->assertSame('second', $provider->getToken());
	}

	private function createProvider(HttpClientInterface $httpClient): NinjaMailTesterDoctrineTokenProvider
	{
		return new NinjaMailTesterDoctrineTokenProvider('foo', new DefaultTokenRepository($this->connection), $httpClient, strict: true);
	}

}
