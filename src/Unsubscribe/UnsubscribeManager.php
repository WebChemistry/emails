<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Unsubscribe;

use InvalidArgumentException;

final readonly class UnsubscribeManager
{

	public const TimeSalt = 'time';
	public const RandomSalt = 'random';
	public const NoSalt = 'no';

	public function __construct(
		private UnsubscribeEncoder $encoder,
		private string $salt = self::RandomSalt,
	)
	{
	}

	public function addToLink(string $link, string $email, ?string $section = null, ?string ...$arguments): string
	{
		$link = rtrim($link, '?&');

		if (str_contains($link, '?u=') || str_contains($link, '&u=')) {
			throw new InvalidArgumentException('Link already contains unsubscribe value.');
		}

		if ($this->salt === self::NoSalt) {
			$value = $this->encoder->encode($email, $section, ...$arguments);
		} else if ($this->salt === self::TimeSalt) {
			$value = $this->encoder->encodeWithTimeSalt($email, $section, ...$arguments);
		} else {
			$value = $this->encoder->encodeWithRandomSalt($email, $section, ...$arguments);
		}

		return $link . (str_contains($link, '?') ? '&' : '?') . 'u=' . $value;
	}

	public function getFromLink(string $link): ?DecodedUnsubscribeValue
	{
		$parsedUrl = parse_url($link, PHP_URL_QUERY);

		if (!is_string($parsedUrl) || $parsedUrl === '') {
			return null;
		}

		parse_str($parsedUrl, $query);

		$param = $query['u'] ?? null;

		if (!is_string($param)) {
			return null;
		}

		return $this->encoder->decode($param);
	}

}
