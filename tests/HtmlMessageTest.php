<?php declare(strict_types = 1);

namespace Tests;

use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\HtmlMessage;

final class HtmlMessageTest extends TestCase
{

	public function testHasNoReplyToByDefault(): void
	{
		$message = new HtmlMessage('<p>Body</p>', 'Subject', new EmailAccount('sender@example.com'));

		$this->assertNull($message->getReplyTo());
	}

	public function testWithReplyToKeepsTheRestOfTheMessage(): void
	{
		$sender = new EmailAccount('sender@example.com', 'Sender');
		$replyTo = new EmailAccount('reply@example.com', 'Reply');
		$message = new HtmlMessage('<p>Body</p>', 'Subject', $sender);

		$answered = $message->withReplyTo($replyTo);

		$this->assertSame(['<p>Body</p>', 'Subject', $sender, $replyTo], [
			$answered->getBody(),
			$answered->getSubject(),
			$answered->getSender(),
			$answered->getReplyTo(),
		]);
		$this->assertNull($message->getReplyTo());
	}

}
