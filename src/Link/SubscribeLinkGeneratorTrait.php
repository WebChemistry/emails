<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Link;

use InvalidArgumentException;
use WebChemistry\Emails\Section\SectionCategory;

trait SubscribeLinkGeneratorTrait
{

	public function addResubscribeQueryParameter(
		string $link,
		string $email,
		SectionCategory $category,
		?string ...$arguments
	): string
	{
		return $this->addQueryParameter('r', $link, $email, $category, $arguments);
	}

	public function loadResubscribeQueryParameter(string $link): ?DecodedResubscribeValue
	{
		return $this->loadQueryParameterByName('r', $link, DecodedResubscribeValue::class);
	}

	public function addUnsubscribeQueryParameter(
		string $link,
		string $email,
		SectionCategory $category,
		?string ...$arguments
	): string
	{
		return $this->addQueryParameter('u', $link, $email, $category, $arguments);
	}

	public function loadUnsubscribeQueryParameter(string $link): ?DecodedUnsubscribeValue
	{
		return $this->loadQueryParameterByName('u', $link, DecodedUnsubscribeValue::class);
	}

	/**
	 * @param array<string|null> $arguments
	 */
	private function addQueryParameter(
		string $parameterName,
		string $link,
		string $email,
		SectionCategory $category,
		array $arguments = [],
	): string
	{
		$link = rtrim($link, '?&');

		if (str_contains($link, "?$parameterName=") || str_contains($link, "&$parameterName=")) {
			throw new InvalidArgumentException("Link already contains $parameterName value.");
		}

		if ($category->isGlobal()) {
			$categoryName = null;
		} else {
			$categoryName = $category->name;
		}

		$sectionName = $category->section->name;

		$value = $this->encoder->encode($email, $sectionName, $categoryName, ...$arguments);

		return $link . (str_contains($link, '?') ? '&' : '?') . "$parameterName=$value";
	}

	public function loadQueryParameter(string $link): DecodedResubscribeValue|DecodedUnsubscribeValue|null
	{
		return $this->loadUnsubscribeQueryParameter($link) ?? $this->loadResubscribeQueryParameter($link);
	}

	/**
	 * @template T of DecodedResubscribeValue|DecodedUnsubscribeValue
	 * @param string $parameterName
	 * @param string $link
	 * @param class-string<T> $constructor
	 * @return T|null
	 */
	private function loadQueryParameterByName(string $parameterName, string $link, string $constructor): DecodedResubscribeValue|DecodedUnsubscribeValue|null
	{
		$parsedUrl = parse_url($link, PHP_URL_QUERY);

		if (!is_string($parsedUrl) || $parsedUrl === '') {
			return null;
		}

		parse_str($parsedUrl, $query);

		$param = $query[$parameterName] ?? null;

		if (!is_string($param)) {
			return null;
		}

		$values = $this->encoder->decode($param);

		if ($values === null) {
			return null;
		}

		$email = $values[0] ?? null;
		$section = $values[1] ?? null;
		$category = $values[2] ?? SectionCategory::Global;

		if (!is_string($email) || !is_string($section)) {
			return null;
		}

		return new $constructor($email, $section, $category, array_slice($values, 3));
	}

}
