<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

final readonly class HtmlMessage implements Message
{

	public function __construct(
		private string $body,
		private string $subject,
		private EmailAccount $sender,
		private ?EmailAccount $replyTo = null,
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

	/**
	 * Where a reply goes instead of the sender, e.g. the person a message to the team is about.
	 */
	public function getReplyTo(): ?EmailAccount
	{
		return $this->replyTo;
	}

	/**
	 * The same message answered to another address; generated templates build their messages without one.
	 */
	public function withReplyTo(EmailAccount $replyTo): self
	{
		return new self($this->body, $this->subject, $this->sender, $replyTo);
	}

}
