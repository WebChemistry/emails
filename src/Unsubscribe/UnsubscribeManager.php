<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Unsubscribe;

use InvalidArgumentException;
use WebChemistry\Emails\Common\Encoder;

final readonly class UnsubscribeManager
{

	public function __construct(
		private Encoder $encoder,
	)
	{
	}

	public function addToLink(string $link, string $email, ?string $section = null, ?string ...$arguments): string
	{
		$link = rtrim($link, '?&');

		if (str_contains($link, '?u=') || str_contains($link, '&u=')) {
			throw new InvalidArgumentException('Link already contains unsubscribe value.');
		}

		$value = $this->encoder->encode($email, $section, ...$arguments);

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

		$values = $this->encoder->decode($param);

		if ($values === null) {
			return null;
		}

		$email = $values[0] ?? null;
		$section = $values[1] ?? null;

		if (!is_string($email)) {
			return null;
		}

		return new DecodedUnsubscribeValue($email, $section, array_slice($values, 2));
	}

}
