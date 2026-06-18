<?php declare(strict_types = 1);

namespace Tests\Validator;

use Tests\TestCase;
use WebChemistry\Emails\Validator\CompoundEmailValidator;
use WebChemistry\Emails\Validator\EmailValidator;
use WebChemistry\Emails\Validator\ValidationResult;

final class CompoundEmailValidatorTest extends TestCase
{

	public function testNoValidatorsReturnsOk(): void
	{
		$validator = new CompoundEmailValidator([]);

		$result = $validator->validate($this->firstEmail);

		$this->assertTrue($result->ok);
		$this->assertSame('OK', $result->errorCode);
		$this->assertSame(CompoundEmailValidator::class, $result->fromClassName);
	}

	public function testAllValidatorsPass(): void
	{
		$validator = new CompoundEmailValidator([$this->passing('First'), $this->passing('Second')]);

		$result = $validator->validate($this->firstEmail);

		$this->assertTrue($result->ok);
		$this->assertSame('Second', $result->fromClassName);
	}

	public function testShortCircuitsOnFirstFailure(): void
	{
		$validator = new CompoundEmailValidator([
			$this->failing('Failing'),
			$this->throwing(),
		]);

		$result = $validator->validate($this->firstEmail);

		$this->assertFalse($result->ok);
		$this->assertSame('Failed', $result->errorCode);
		$this->assertSame('Failing', $result->fromClassName);
	}

	private function passing(string $fromClassName): EmailValidator
	{
		return new class ($fromClassName) implements EmailValidator {

			public function __construct(
				private string $fromClassName,
			)
			{
			}

			public function validate(string $email, array $options = []): ValidationResult
			{
				return new ValidationResult(true, 'OK', $this->fromClassName);
			}

		};
	}

	private function failing(string $fromClassName): EmailValidator
	{
		return new class ($fromClassName) implements EmailValidator {

			public function __construct(
				private string $fromClassName,
			)
			{
			}

			public function validate(string $email, array $options = []): ValidationResult
			{
				return new ValidationResult(false, 'Failed', $this->fromClassName);
			}

		};
	}

	private function throwing(): EmailValidator
	{
		return new class implements EmailValidator {

			public function validate(string $email, array $options = []): ValidationResult
			{
				throw new \LogicException('Should not be reached because of short circuit.');
			}

		};
	}

}
