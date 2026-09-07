<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Common;

use LogicException;
use SensitiveParameter;

final class Encoder
{

	private const Version = 'v1';
	private const RequiredParts = 3;

	public function __construct(
		#[SensitiveParameter]
		private string $secret,
		private EncodeType $type = EncodeType::Salt,
	)
	{
	}

	public static function fromBase64(#[SensitiveParameter] string $secret, EncodeType $type = EncodeType::Salt): self
	{
		$decoded = base64_decode($secret, true);

		if ($decoded === false) {
			throw new LogicException('Invalid secret.');
		}

		return new self($decoded, $type);
	}

	public function withType(EncodeType $type): self
	{
		return new self($this->secret, $type);
	}

	/**
	 * @return non-empty-string
	 */
	public function encode(string $value, ?string ...$arguments): string
	{
		// Trailing nulls carry nothing, so they go before the salt or iv joins the arguments - after it they
		// would never be the last element and every omitted optional argument would add an empty segment.
		while (count($arguments) > 0 && end($arguments) === null) {
			array_pop($arguments);
		}

		$iv = '';

		if ($this->type === EncodeType::Salt) {
			$arguments[] = bin2hex(random_bytes(10));
		} else if ($this->type === EncodeType::Encrypted) {
			/** @var int|false $length */
			$length = openssl_cipher_iv_length('aes-256-cbc');

			if ($length === false) {
				throw new LogicException('Invalid cipher.');
			}

			$arguments['iv'] = $iv = openssl_random_pseudo_bytes($length);
		}

		$values = [
			self::Version,
			$this->type->value,
			$this->hash($this->type->value, $value, ...$arguments) . $this->encodeValue($value, $iv),
		];

		foreach ($arguments as $key => $argument) {
			if ($key !== 'iv') {
				$values[] = $this->encodeValue($argument, $iv);
			} else {
				$values[] = $this->encodeValue($argument, '');
			}
		}

		return implode('.', $values);
	}

	/**
	 * @return non-empty-list<string|null>|null
	 */
	public function decode(string $hash): ?array
	{
		$parts = explode('.', $hash);

		if (count($parts) < self::RequiredParts) {
			return null;
		}

		[$version, $type, $hash] = $parts;

		if ($version !== self::Version) {
			return null;
		}

		$type = EncodeType::tryFrom($type);

		if ($type === null) {
			return null;
		}

		$arguments = array_slice($parts, self::RequiredParts);
		$iv = '';

		if ($type === EncodeType::Encrypted) {
			$iv = array_pop($arguments);

			if (!is_string($iv)) {
				return null;
			}

			$iv = $this->decodeValue($iv, '');

			// A corrupted iv segment is bad input like any other; openssl would only warn and truncate it.
			if ($iv === null || strlen($iv) !== openssl_cipher_iv_length('aes-256-cbc')) {
				return null;
			}
		}

		$value = $this->decodeValue(substr($hash, 64), $iv);

		if ($value === null) {
			return null;
		}

		$values = [$value];

		foreach ($arguments as $argument) {
			$values[] = $this->decodeValue($argument, $iv);
		}

		if ($iv !== '') {
			$values[] = $iv;
		}

		if (hash_equals($this->hash($type->value, ...$values), substr($hash, 0, 64)) === false) {
			return null;
		}

		if ($type !== EncodeType::Basic) {
			array_pop($values);
		}

		if (count($values) === 0) {
			return null;
		}

		return $values;
	}

	private function encodeValue(?string $val, string $iv): string
	{
		if ($val === null) {
			return '';
		}

		if ($iv !== '') {
			$val = openssl_encrypt($val, 'aes-256-cbc', $this->secret, 0, $iv);

			if ($val === false) {
				throw new LogicException('Encryption failed.');
			}
		}

		return rtrim(strtr(base64_encode($val), '+/', '-_'), '=');
	}

	private function decodeValue(string $val, string $iv): ?string
	{
		if ($val === '') {
			return null;
		}

		$decoded = base64_decode(strtr($val, '-_', '+/'), true);

		if ($decoded === false) {
			return null;
		}

		if ($iv !== '') {
			$decoded = openssl_decrypt($decoded, 'aes-256-cbc', $this->secret, 0, $iv);

			// A tampered segment or a token from another secret does not decrypt (the padding no longer
			// checks out); that is bad input to reject, like a failed signature, not a fault in the encoder.
			if ($decoded === false) {
				return null;
			}
		}

		return $decoded;
	}

	private function hash(?string ...$vals): string
	{
		return hash_hmac('sha256', implode('.', $vals), $this->secret);
	}

}
