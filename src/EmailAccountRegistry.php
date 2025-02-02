<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

final class EmailAccountRegistry implements EmailRegistry
{

	/** @var array<string, array-key> */
	private ?array $index = null;

	/** @var string[] */
	private array $removed = [];

	/**
	 * @param EmailAccount[] $accounts
	 */
	public function __construct(
		private array $accounts,
	)
	{
	}

	public function isEmpty(): bool
	{
		return !$this->accounts;
	}

	/**
	 * @return string[]
	 */
	public function getEmails(): array
	{
		return array_map(fn (EmailAccount $account) => $account->email, $this->accounts);
	}

	/**
	 * @return EmailAccount[]
	 */
	public function getAccounts(): array
	{
		return $this->accounts;
	}

	public function remove(string $email): void
	{
		$index = $this->getIndex();

		if (isset($index[$email])) {
			unset($this->accounts[$index[$email]]);

			$this->removed[] = $email;
		}
	}

	/**
	 * @return array<string, array-key>
	 */
	private function getIndex(): array
	{
		if ($this->index === null) {
			$index = [];

			foreach ($this->accounts as $key => $account) {
				$index[$account->email] = $key;
			}

			$this->index = $index;
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
