<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

final class StringEmailRegistry implements EmailRegistry
{

	/** @var array<string, array-key> */
	private ?array $index = null;

	/** @var string[] */
	private array $removed = [];

	/**
	 * @param string[] $emails
	 */
	public function __construct(
		private array $emails,
	)
	{
	}

	public function isEmpty(): bool
	{
		return !$this->emails;
	}

	/**
	 * @return string[]
	 */
	public function getEmails(): array
	{
		return $this->emails;
	}

	public function remove(string $email): void
	{
		$index = $this->getIndex();

		if (isset($index[$email])) {
			unset($this->emails[$index[$email]]);

			$this->removed[] = $email;
		}
	}

	/**
	 * @return array<string, array-key>
	 */
	private function getIndex(): array
	{
		if ($this->index === null) {
			$this->index = array_flip($this->emails);
		}

		return $this->index;
	}

	/**
	 * @return string[]
	 */
	public function getRemoved(): array
	{
		return $this->removed;
	}

}
