<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

final class TemplateMessage implements Message
{

	public function __construct(
		private string $template,
		private ?string $subject = null,
		private ?EmailAccount $sender = null,
	)
	{
	}

	public function getTemplate(): string
	{
		return $this->template;
	}

	public function getSubject(): ?string
	{
		return $this->subject;
	}

	public function getSender(): ?EmailAccount
	{
		return $this->sender;
	}

}
