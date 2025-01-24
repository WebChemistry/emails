<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

final readonly class HtmlMessage implements Message
{

	public function __construct(
		private string $body,
		private string $subject,
		private EmailAccount $sender,
	)
	{
	}

	public function getBody(): string
	{
		return $this->body;
	}

	public function getSubject(): string
	{
		return $this->subject;
	}

	public function getSender(): EmailAccount
	{
		return $this->sender;
	}

}
