<?php declare(strict_types = 1);

namespace Tests\Token;

use Symfony\Component\Clock\Test\ClockSensitiveTrait;
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
	use ClockSensitiveTrait;

	public function testInsert(): void
	{
		self::mockTime('2021-01-01 12:00:00');

		$httpClient = new MockHttpClient([
			MockResponse::fromFile(__DIR__ . '/http/mail_tester_ninja_token.json'),
		]);
		$provider = $this->createProvider($httpClient);

		$token = $provider->getToken();
		$this->assertSame('Mk5ETGRlQnd3WkZYOGszWXlwQV...WDhQR0pJU0tLa1R4WW5BbXRJVA==', $token->value);
		$this->assertSame('2021-01-01 12:00:00', $token->createdAt->format('Y-m-d H:i:s'));
	}

	public function testUpsert(): void
	{
		self::mockTime('2021-01-01 12:00:00');

		$httpClient = new MockHttpClient([
			MockResponse::fromFile(__DIR__ . '/http/mail_tester_ninja_token.json'),
			MockResponse::fromFile(__DIR__ . '/http/mail_tester_ninja_token_second.json'),
		]);
		$provider = $this->createProvider($httpClient);

		$token = $provider->update();

		$this->assertSame('Mk5ETGRlQnd3WkZYOGszWXlwQV...WDhQR0pJU0tLa1R4WW5BbXRJVA==', $token->value);
		$this->assertSame('2021-01-01 12:00:00', $token->createdAt->format('Y-m-d H:i:s'));
		$this->assertSame([
			[
				'id' => 'mail_tester_ninja',
				'token' => 'Mk5ETGRlQnd3WkZYOGszWXlwQV...WDhQR0pJU0tLa1R4WW5BbXRJVA==',
				'created' => '2021-01-01 12:00:00',
			],
		], $this->databaseSnapshot());

		self::mockTime('2021-01-01 13:00:00');

		$token = $provider->update();
		$this->assertSame('second', $token->value);
		$this->assertSame('2021-01-01 13:00:00', $token->createdAt->format('Y-m-d H:i:s'));
		$this->assertSame([
			[
				'id' => 'mail_tester_ninja',
				'token' => 'second',
				'created' => '2021-01-01 13:00:00',
			],
		], $this->databaseSnapshot());
		$this->assertSame(2, $httpClient->getRequestsCount());

		$provider->reset();

		$token = $provider->getToken();
		$this->assertSame('second', $token->value);
		$this->assertSame('2021-01-01 13:00:00', $token->createdAt->format('Y-m-d H:i:s'));
	}

	private function createProvider(HttpClientInterface $httpClient): NinjaMailTesterDoctrineTokenProvider
	{
		return new NinjaMailTesterDoctrineTokenProvider('foo', new DefaultTokenRepository($this->connection), $httpClient, strict: true);
	}

	/**
	 * @return mixed[]
	 */
	private function databaseSnapshot(): array
	{
		return $this->connection->createQueryBuilder()
			->select('id, token, created')
			->from('tokens')
			->orderBy('created', 'ASC')
			->executeQuery()->fetchAllAssociative();
	}

}
