<?php declare(strict_types = 1);

namespace Tests\Adapter;

use Nette\Utils\FileSystem;
use Tests\TestCase;
use WebChemistry\Emails\Adapter\FileAdapter;
use WebChemistry\Emails\EmailAccount;
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

}
