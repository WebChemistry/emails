<?php declare(strict_types = 1);

namespace Tests;

use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\EmailAttachment;
use WebChemistry\Emails\HtmlMessage;

final class HtmlMessageTest extends TestCase
{

	public function testHasNoReplyToByDefault(): void
	{
		$message = new HtmlMessage('<p>Body</p>', 'Subject', new EmailAccount('sender@example.com'));

		$this->assertNull($message->getReplyTo());
	}

	public function testHasNoAttachmentsByDefault(): void
	{
		$message = new HtmlMessage('<p>Body</p>', 'Subject', new EmailAccount('sender@example.com'));

		$this->assertSame([], $message->getAttachments());
	}

	public function testWithAttachmentAppendsAndKeepsTheRestOfTheMessage(): void
	{
		$sender = new EmailAccount('sender@example.com', 'Sender');
		$replyTo = new EmailAccount('reply@example.com', 'Reply');
		$first = new EmailAttachment('first.png', 'first-bytes', 'image/png');
		$second = new EmailAttachment('second.txt', 'second-bytes', 'text/plain');
		$message = new HtmlMessage('<p>Body</p>', 'Subject', $sender, $replyTo);

		$attached = $message->withAttachment($first)->withAttachment($second);

		$this->assertSame(['<p>Body</p>', 'Subject', $sender, $replyTo, [$first, $second]], [
			$attached->getBody(),
			$attached->getSubject(),
			$attached->getSender(),
			$attached->getReplyTo(),
			$attached->getAttachments(),
		]);
		$this->assertSame([], $message->getAttachments());
	}

	public function testWithReplyToKeepsTheAttachments(): void
	{
		$attachment = new EmailAttachment('screenshot.png', 'bytes', 'image/png');
		$message = new HtmlMessage('<p>Body</p>', 'Subject', new EmailAccount('sender@example.com'), attachments: [$attachment]);

		$answered = $message->withReplyTo(new EmailAccount('reply@example.com'));

		$this->assertSame([$attachment], $answered->getAttachments());
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
