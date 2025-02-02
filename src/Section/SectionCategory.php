<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Section;

final readonly class SectionCategory
{

	public const Global = '*';

	public function __construct(
		public Section $section,
		public string $name,
	)
	{
	}

	public function isUnsubscribable(): bool
	{
		return $this->section->isUnsubscribable();
	}

	/**
	 * @template T
	 * @param array<string, array<string, T>> $array
	 * @return T|null
	 */
	public function accessMultidimensionalArray(array $array): mixed
	{
		if ($this->name === SectionCategory::Global) {
			return $array[$this->section->name][SectionCategory::Global] ?? null;
		} else {
			return $array[$this->section->name][SectionCategory::Global] ?? $array[$this->section->name][$this->name] ?? null;
		}
	}

	public function isGlobal(): bool
	{
		return $this->name === self::Global;
	}

}
