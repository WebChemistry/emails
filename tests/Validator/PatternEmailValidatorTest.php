<?php declare(strict_types = 1);

namespace Tests\Validator;

use Tests\TestCase;
use WebChemistry\Emails\Validator\PatternEmailValidator;

final class PatternEmailValidatorTest extends TestCase
{

	public function testValidEmail(): void
	{
		$validator = new PatternEmailValidator();
		$result = $validator->validate('test@example.com');

		$this->assertTrue($result->ok);
		$this->assertSame('OK', $result->errorCode);
		$this->assertSame(PatternEmailValidator::class, $result->fromClassName);
	}

	public function testInvalidEmail(): void
	{
		$validator = new PatternEmailValidator();
		$result = $validator->validate('invalid-email');

		$this->assertFalse($result->ok);
		$this->assertSame(PatternEmailValidator::Code, $result->errorCode);
		$this->assertSame(PatternEmailValidator::class, $result->fromClassName);
	}

	public function testEmailWithoutAtSymbol(): void
	{
		$validator = new PatternEmailValidator();
		$result = $validator->validate('emailexample.com');

		$this->assertFalse($result->ok);
		$this->assertSame('InvalidEmail', $result->errorCode);
	}

	public function testEmailWithoutDomain(): void
	{
		$validator = new PatternEmailValidator();
		$result = $validator->validate('test@');

		$this->assertFalse($result->ok);
		$this->assertSame('InvalidEmail', $result->errorCode);
	}

	public function testEmptyEmail(): void
	{
		$validator = new PatternEmailValidator();
		$result = $validator->validate('');

		$this->assertFalse($result->ok);
		$this->assertSame('InvalidEmail', $result->errorCode);
	}

	public function testComplexValidEmail(): void
	{
		$validator = new PatternEmailValidator();
		$result = $validator->validate('user.name+tag@example-domain.co.uk');

		$this->assertTrue($result->ok);
		$this->assertSame('OK', $result->errorCode);
	}

}