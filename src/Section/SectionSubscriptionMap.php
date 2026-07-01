<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Section;

interface SectionSubscriptionMap
{

	/**
	 * @return array<string, bool>
	 */
	public function get(): array;

	/**
	 * @param array<string, bool> $values
	 */
	public function set(array $values): void;

}
