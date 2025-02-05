<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter\InMemory;

use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\HtmlMessage;
use WebChemistry\Emails\Message;

final readonly class InMemorySentEmail
{

	/**
	 * @param mixed[] $options
	 */
	public function __construct(
		public EmailAccount $account,
		public Message $message,
		public array $options = [],
	)
	{
	}

	public function getEmail(): string
	{
		return $this->account->email;
	}

	public function getBody(): string
	{
		if (!$this->message instanceof HtmlMessage) {
			return '';
		}

		return $this->message->getBody();
	}

}
