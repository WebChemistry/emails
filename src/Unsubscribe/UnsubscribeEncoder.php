<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Unsubscribe;

use Psr\Clock\ClockInterface;
use SensitiveParameter;
use Symfony\Component\Clock\DatePoint;

final class UnsubscribeEncoder
{

	private const Version = 'v1';
	private const TypeNormal = 'n';
	private const TypeTimeSalt = 't';
	private const TypeRandomSalt = 'r';
	private const RequiredParts = 3;

	public function __construct(
		#[SensitiveParameter]
		private string $secret,
		private ?ClockInterface $clock = null,
	)
	{
	}

	public function encode(string $email, ?string $section = null, ?string ...$arguments): string
	{
		return $this->_encode(self::TypeNormal, $email, $section, $arguments);
	}

	public function encodeWithTimeSalt(string $email, ?string $section = null, ?string ...$arguments): string
	{
		$arguments[] = (string) ($this->clock ? $this->clock->now()->getTimestamp() : time());

		return $this->_encode(self::TypeTimeSalt, $email, $section, $arguments);
	}

	public function encodeWithRandomSalt(string $email, ?string $section = null, ?string ...$arguments): string
	{
		$arguments[] = bin2hex(random_bytes(10));

		return $this->_encode(self::TypeRandomSalt, $email, $section, $arguments);
	}

	/**
	 * @param array<string|null> $arguments
	 */
	private function _encode(string $type, string $email, ?string $section = null, array $arguments = []): string
	{
		$values = [
			self::Version,
			$type,
			$this->hash($type, $email, $section, ...$arguments) . $this->base64Encode($email),
			$this->base64Encode($section),
		];

		foreach ($arguments as $argument) {
			$values[] = $this->base64Encode($argument);
		}

		return rtrim(implode('.', $values), '.');
	}

	public function decode(string $hash): ?DecodedUnsubscribeValue
	{
		$parts = explode('.', $hash);

		if (count($parts) < self::RequiredParts) {
			return null;
		}

		[$version, $type, $hash] = $parts;

		if ($version !== self::Version) {
			return null;
		}

		$email = $this->base64Decode(substr($hash, 64));

		if ($email === null) {
			return null;
		}

		$section = isset($parts[self::RequiredParts]) ? $this->base64Decode($parts[self::RequiredParts]) : null;

		$arguments = [];

		foreach (array_slice($parts, self::RequiredParts + 1) as $argument) {
			$arguments[] = $this->base64Decode($argument);
		}

		if (hash_equals($this->hash($type, $email, $section, ...$arguments), substr($hash, 0, 64)) === false) {
			return null;
		}

		if (in_array($type, [self::TypeTimeSalt, self::TypeRandomSalt], true)) {
			array_pop($arguments);
		}

		return new DecodedUnsubscribeValue($email, $section, $arguments);
	}

	private function base64Encode(?string $val): string
	{
		if ($val === null) {
			return '';
		}

		return rtrim(strtr(base64_encode($val), '+/', '-_'), '=');
	}

	private function base64Decode(string $val): ?string
	{
		if ($val === '') {
			return null;
		}

		$decoded = base64_decode(strtr($val, '-_', '+/'), true);

		if ($decoded === false) {
			return null;
		}

		return $decoded;
	}

	private function hash(?string ...$vals): string
	{
		return hash_hmac('sha256', implode('.', $vals), $this->secret);
	}

}
