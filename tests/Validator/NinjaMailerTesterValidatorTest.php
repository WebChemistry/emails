<?php declare(strict_types = 1);

namespace Tests\Validator;

use DateTimeImmutable;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\DatabaseEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\Token\StaticTokenProvider;
use WebChemistry\Emails\Token\Token;
use WebChemistry\Emails\Token\TokenProvider;
use WebChemistry\Emails\Validator\NinjaMailerTesterValidator;

final class NinjaMailerTesterValidatorTest extends TestCase
{

	use DatabaseEnvironment;
	use ClockSensitiveTrait;

	public function testNoRefresh(): void
	{
		self::mockTime('2021-01-01 12:00:00');

		$tokenProvider = new StaticTokenProvider('static');
		$httpClient = new MockHttpClient([
			MockResponse::fromFile(__DIR__ . '/http/ok.json'),
		]);
		$validator = new NinjaMailerTesterValidator($tokenProvider, $httpClient);

		$validator->validate('john.doe@example.com');

		$this->assertSame(1, $httpClient->getRequestsCount());
	}

	public function testRefresh(): void
	{
		self::mockTime('2021-01-01 12:00:00');

		$tokenProvider = new class implements TokenProvider {

			public bool $updated = false;

			public function getToken(): Token
			{
				return new Token('original', new DateTimeImmutable('2021-01-01 12:00:00'));
			}

			public function update(): Token
			{
				$this->updated = true;

				return new Token('updated');
			}

		};
		$httpClient = new MockHttpClient([
			MockResponse::fromFile(__DIR__ . '/http/ok.json'),
		]);
		$validator = new NinjaMailerTesterValidator($tokenProvider, $httpClient);

		self::mockTime('2021-01-02 12:00:00');

		$validator->validate('john.doe@example.com');

		$this->assertTrue($tokenProvider->updated);
		$this->assertSame(1, $httpClient->getRequestsCount());
	}

}
