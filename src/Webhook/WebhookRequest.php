<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Webhook;

use OutOfBoundsException;
use Psr\Http\Message\RequestInterface;

final readonly class WebhookRequest
{

	/** @var array<string, string> */
	private array $headers;

	private string $method;

	/**
	 * @param array<string, string> $headers
	 */
	public function __construct(
		string $method,
		public string $body,
		array $headers = [],
	)
	{
		$this->method = strtoupper($method);
		$this->headers = $this->normalizeHeaders($headers);
	}

	public function isEmptyBody(): bool
	{
		return $this->body === '';
	}

	public function isPostMethod(): bool
	{
		return $this->method === 'POST';
	}

	public function isGetMethod(): bool
	{
		return $this->method === 'GET';
	}

	public function isPatchMethod(): bool
	{
		return $this->method === 'PATCH';
	}

	public function isPutMethod(): bool
	{
		return $this->method === 'PUT';
	}

	public function hasHeader(string $name): bool
	{
		return isset($this->headers[strtolower($name)]);
	}

	public function getHeaderOrNull(string $name): ?string
	{
		return $this->headers[strtolower($name)] ?? null;
	}

	public function getHeader(string $name): string
	{
		if (!$this->hasHeader($name)) {
			throw new OutOfBoundsException(sprintf('Header %s not found.', $name));
		}

		return $this->headers[strtolower($name)];
	}

	/**
	 * @param array<string, string> $headers
	 * @return array<string, string>
	 */
	private function normalizeHeaders(array $headers): array
	{
		$normalized = [];

		foreach ($headers as $key => $value) {
			$normalized[strtolower($key)] = $value;
		}

		return $normalized;
	}

	public static function fromPsr(RequestInterface $request): self
	{
		$headers = [];

		foreach ($request->getHeaders() as $name => $_) {
			$headers[$name] = $request->getHeaderLine($name);
		}

		return new self($request->getMethod(), $request->getBody()->getContents(), $headers);
	}

}

