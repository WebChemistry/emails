<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

final readonly class HtmlMessage implements Message
{

	/**
	 * @param list<EmailAttachment> $attachments
	 */
	public function __construct(
		private string $body,
		private string $subject,
		private EmailAccount $sender,
		private ?EmailAccount $replyTo = null,
		private array $attachments = [],
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
	 * The files sent with the message, in the order they were added.
	 *
	 * @return list<EmailAttachment>
	 */
	public function getAttachments(): array
	{
		return $this->attachments;
	}

	/**
	 * The same message answered to another address; generated templates build their messages without one.
	 */
	public function withReplyTo(EmailAccount $replyTo): self
	{
		return new self($this->body, $this->subject, $this->sender, $replyTo, $this->attachments);
	}

	/**
	 * The same message with one more file after the ones it already carries.
	 */
	public function withAttachment(EmailAttachment $attachment): self
	{
		return new self($this->body, $this->subject, $this->sender, $this->replyTo, [...$this->attachments, $attachment]);
	}

}
