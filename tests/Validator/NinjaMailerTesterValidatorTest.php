<?php declare(strict_types = 1);

namespace Tests\Validator;

use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\DatabaseEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Validator\NinjaMailerTesterValidator;

final class NinjaMailerTesterValidatorTest extends TestCase
{

	use DatabaseEnvironment;
	use ClockSensitiveTrait;

	public function testNoRefresh(): void
	{
		self::mockTime('2021-01-01 12:00:00');

		$httpClient = new MockHttpClient([
			MockResponse::fromFile(__DIR__ . '/http/ok.json'),
		]);
		$validator = new NinjaMailerTesterValidator('SECRET', $httpClient, retryDelayMs: 10);

		$validator->validate('john.doe@example.com');

		$this->assertSame(1, $httpClient->getRequestsCount());
	}

	public function testRetryOnTransportError(): void
	{
		$httpClient = new MockHttpClient([
			new MockResponse('', ['error' => 'Connection refused']),
			new MockResponse('', ['error' => 'Connection refused']),
			MockResponse::fromFile(__DIR__ . '/http/ok.json'),
		]);
		$validator = new NinjaMailerTesterValidator('SECRET', $httpClient, retryDelayMs: 10);

		$result = $validator->validate('john.doe@example.com');

		$this->assertTrue($result->ok);
		$this->assertSame('Accepted', $result->errorCode);
		$this->assertSame(3, $httpClient->getRequestsCount());
	}

	public function testRetryOnHttpError(): void
	{
		$httpClient = new MockHttpClient([
			new MockResponse('', ['http_code' => 500]),
			new MockResponse('', ['http_code' => 502]),
			MockResponse::fromFile(__DIR__ . '/http/ok.json'),
		]);
		$validator = new NinjaMailerTesterValidator('SECRET', $httpClient, retryDelayMs: 10);

		$result = $validator->validate('john.doe@example.com');

		$this->assertTrue($result->ok);
		$this->assertSame('Accepted', $result->errorCode);
		$this->assertSame(3, $httpClient->getRequestsCount());
	}

	public function testExhaustedRetries(): void
	{
		$httpClient = new MockHttpClient([
			new MockResponse('', ['http_code' => 500]),
			new MockResponse('', ['http_code' => 500]),
			new MockResponse('', ['http_code' => 500]),
			new MockResponse('', ['http_code' => 500]),
		]);
		$validator = new NinjaMailerTesterValidator('SECRET', $httpClient, retryDelayMs: 10);

		$this->expectException(ServerException::class);

		$validator->validate('john.doe@example.com');
	}

}
