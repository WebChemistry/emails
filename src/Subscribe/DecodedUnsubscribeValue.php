<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Subscribe;

use WebChemistry\Emails\EmailManager;

final readonly class DecodedUnsubscribeValue
{

	/**
	 * @param list<string|null> $arguments
	 */
	public function __construct(
		public string $email,
		public string $section,
		public string $category = EmailManager::GlobalCategory,
		public array $arguments = [],
	)
	{
	}

}
