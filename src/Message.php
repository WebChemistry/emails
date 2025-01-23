<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

interface Message
{

	public function getSubject(): ?string;

	public function getSender(): ?EmailAccount;

}
