<?php declare(strict_types = 1);

namespace Tests\Adapter;

use Nette\Utils\FileSystem;
use Tests\TestCase;
use WebChemistry\Emails\Adapter\FileAdapter;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\EmailAttachment;
use WebChemistry\Emails\HtmlMessage;
use WebChemistry\Emails\TemplateMessage;

final class FileAdapterTest extends TestCase
{

	/** @var list<string> */
	private array $temporaryDirectories = [];

	public function testAppendShortSentEmailLog(): void
	{
		$directory = $this->createTemporaryDirectory();
		$logFile = sprintf('%s/emails.log', $directory);
		$sender = new EmailAccount('sender@example.com', 'Sender');
		$adapter = new FileAdapter($directory, $logFile);

		$adapter->send(
			[$this->firstEmailAccount, $this->secondEmailAccount],
			new HtmlMessage(sprintf('<p>%s</p>', str_repeat('content ', 100)), 'HTML subject', $sender),
		);
		$adapter->send(
			[$this->thirdEmailAccount],
			new TemplateMessage('welcome', 'Template subject', $sender),
		);

		$logFileContent = file_get_contents($logFile);

		$this->assertIsString($logFileContent);
		$this->assertStringMatchesFormatFile(__DIR__ . '/expected/sent-email.log', $logFileContent);
	}

	public function testLogsLongTextWithMultibyteCharacters(): void
	{
		$directory = $this->createTemporaryDirectory();
		$logFile = sprintf('%s/emails.log', $directory);
		$sender = new EmailAccount('sender@example.com', 'Sender');
		$adapter = new FileAdapter($directory, $logFile);

		// Prefix with single-byte chars so the 500-byte cut lands inside a multibyte character.
		$longBody = sprintf('<p>%s%s</p>', str_repeat('a', 3), str_repeat('🚀', 600));
		$longSubject = sprintf('%s%s', str_repeat('a', 3), str_repeat('🚀', 600));

		$adapter->send(
			[$this->firstEmailAccount],
			new HtmlMessage($longBody, $longSubject, $sender),
		);

		$logFileContent = file_get_contents($logFile);

		$this->assertIsString($logFileContent);
		$this->assertJson($logFileContent);
	}

	public function testRecordsReplyToOfHtmlMessage(): void
	{
		$directory = $this->createTemporaryDirectory();
		$adapter = new FileAdapter($directory);
		$message = new HtmlMessage('<p>Content</p>', 'Subject', new EmailAccount('sender@example.com', 'Sender'));

		$adapter->send([$this->firstEmailAccount], $message->withReplyTo(new EmailAccount('reply@example.com', 'Reply')));

		$jsonFiles = glob(sprintf('%s/send_*.json', $directory));

		$this->assertIsArray($jsonFiles);
		$this->assertCount(1, $jsonFiles);

		$json = file_get_contents($jsonFiles[0]);

		$this->assertIsString($json);

		/** @var array{message: array<string, string>} $values */
		$values = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
		$sentMessage = $values['message'];

		// The captured body's file name carries the time and a unique id; the rest is compared whole.
		$this->assertStringMatchesFormat('send_%s.%s.html', $sentMessage['file']);
		unset($sentMessage['file']);

		$this->assertSame([
			'sender' => 'Sender <sender@example.com>',
			'subject' => 'Subject',
			'reply_to' => 'Reply <reply@example.com>',
		], $sentMessage);
	}

	public function testWritesAttachmentsOfHtmlMessageBesideTheBody(): void
	{
		$directory = $this->createTemporaryDirectory();
		$adapter = new FileAdapter($directory);
		$message = (new HtmlMessage('<p>Content</p>', 'Subject', new EmailAccount('sender@example.com', 'Sender')))
			->withAttachment(new EmailAttachment('screen shot.png', 'png-bytes', 'image/png'))
			->withAttachment(new EmailAttachment('../notes.txt', 'text-bytes', 'text/plain'));

		$adapter->send([$this->firstEmailAccount], $message);

		[$capturePrefix, $values] = $this->readOnlySendCapture($directory);

		/** @var array{message: array{file: string, attachments: list<array{file: string, name: string, content_type: string}>}} $values */
		$sentMessage = $values['message'];

		$this->assertSame(sprintf('%s.html', $capturePrefix), $sentMessage['file']);
		$this->assertSame([
			[
				'file' => sprintf('%s.0-screen-shot.png', $capturePrefix),
				'name' => 'screen shot.png',
				'content_type' => 'image/png',
			],
			[
				'file' => sprintf('%s.1-notes.txt', $capturePrefix),
				'name' => '../notes.txt',
				'content_type' => 'text/plain',
			],
		], $sentMessage['attachments']);
		$this->assertSame(
			['png-bytes', 'text-bytes'],
			array_map(
				fn (array $attachment): string|false => file_get_contents(sprintf('%s/%s', $directory, $attachment['file'])),
				$sentMessage['attachments'],
			),
		);
	}

	public function testNamesAttachmentWithoutUsableFileNameGenerically(): void
	{
		$directory = $this->createTemporaryDirectory();
		$adapter = new FileAdapter($directory);
		$message = (new HtmlMessage('<p>Content</p>', 'Subject', new EmailAccount('sender@example.com')))
			->withAttachment(new EmailAttachment('/', 'bytes', 'application/octet-stream'));

		$adapter->send([$this->firstEmailAccount], $message);

		[$capturePrefix, $values] = $this->readOnlySendCapture($directory);

		/** @var array{message: array{attachments: list<array{file: string}>}} $values */
		$attachmentFileName = $values['message']['attachments'][0]['file'];

		$this->assertSame(sprintf('%s.0-attachment', $capturePrefix), $attachmentFileName);
		$this->assertSame('bytes', file_get_contents(sprintf('%s/%s', $directory, $attachmentFileName)));
	}

	public function testSentEmailLogFileIsOptional(): void
	{
		$directory = $this->createTemporaryDirectory();
		$adapter = new FileAdapter($directory);

		$adapter->send(
			[$this->firstEmailAccount],
			new HtmlMessage('<p>Content</p>', 'Subject', new EmailAccount('sender@example.com')),
		);

		$logFiles = glob(sprintf('%s/*.log', $directory));

		$this->assertSame([], $logFiles);
	}

	protected function tearDown(): void
	{
		foreach ($this->temporaryDirectories as $temporaryDirectory) {
			FileSystem::delete($temporaryDirectory);
		}
	}

	private function createTemporaryDirectory(): string
	{
		$directory = sprintf('%s/webchemistry-emails-%s', sys_get_temp_dir(), uniqid());

		FileSystem::createDir($directory);

		$this->temporaryDirectories[] = $directory;

		return $directory;
	}

	/**
	 * The prefix every file of the only send in the directory shares, and the values of its json.
	 *
	 * @return array{string, mixed}
	 */
	private function readOnlySendCapture(string $directory): array
	{
		$jsonFiles = glob(sprintf('%s/send_*.json', $directory));

		$this->assertIsArray($jsonFiles);
		$this->assertCount(1, $jsonFiles);

		$json = file_get_contents($jsonFiles[0]);

		$this->assertIsString($json);

		return [basename($jsonFiles[0], '.json'), json_decode($json, true, flags: JSON_THROW_ON_ERROR)];
	}

}
