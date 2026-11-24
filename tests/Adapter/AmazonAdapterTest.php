<?php declare(strict_types = 1);

namespace Tests\Adapter;

use InvalidArgumentException;
use Tests\TestCase;
use WebChemistry\Emails\Adapter\AmazonAdapter;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\EmailAttachment;
use WebChemistry\Emails\HtmlMessage;

final class AmazonAdapterTest extends TestCase
{

	public function testRejectsHtmlMessageWithAttachments(): void
	{
		// The check runs before any request, so made-up credentials never reach SES.
		$adapter = new AmazonAdapter('eu-central-1', 'access-key', 'secret-key');
		$message = (new HtmlMessage('<p>Content</p>', 'Subject', new EmailAccount('sender@example.com')))
			->withAttachment(new EmailAttachment('notes.txt', 'text-bytes', 'text/plain'));

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Attachments are not supported by the Amazon adapter.');

		$adapter->send([$this->firstEmailAccount], $message);
	}

}
