<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Exception;

use RuntimeException;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class HttpClientException extends RuntimeException
{

	public function __construct(
		private ResponseInterface $response,
	)
	{
		parent::__construct(sprintf(
			'HTTP request failed with status code %d and content: %s',
			$response->getStatusCode(),
			$response->getContent(false),
		));
	}

	public function getStatusCode(): int
	{
		return $this->response->getStatusCode();
	}

	public function getJsonContent(): mixed
	{
		$headers = $this->response->getHeaders(false);
		$contentTypes = $headers['content-type'] ?? [];

		foreach ($contentTypes as $contentType) {
			if (str_contains($contentType, 'application/json')) {
				return $this->response->toArray(false);
			}
		}

		return null;
	}

}
