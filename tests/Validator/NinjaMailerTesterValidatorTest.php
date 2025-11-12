<?php declare(strict_types = 1);

namespace Tests\Validator;

use Symfony\Component\Clock\Test\ClockSensitiveTrait;
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
		$validator = new NinjaMailerTesterValidator('SECRET', $httpClient);

		$validator->validate('john.doe@example.com');

		$this->assertSame(1, $httpClient->getRequestsCount());
	}

}
