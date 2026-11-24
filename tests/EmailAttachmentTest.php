<?php declare(strict_types = 1);

namespace Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use WebChemistry\Emails\EmailAttachment;

final class EmailAttachmentTest extends TestCase
{

	public function testKeepsTheGivenValues(): void
	{
		$attachment = new EmailAttachment('faktura č. 1.pdf', 'pdf-bytes', 'text/plain; charset=utf-8');

		$this->assertSame(
			['faktura č. 1.pdf', 'pdf-bytes', 'text/plain; charset=utf-8'],
			[$attachment->fileName, $attachment->content, $attachment->contentType],
		);
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function provideInvalidFileNames(): iterable
	{
		yield 'empty' => [''];
		yield 'quote' => ['report".pdf'];
		yield 'backslash' => ['report\\.pdf'];
		yield 'line break' => ["report.pdf\r\nContent-Type: text/html"];
		yield 'null byte' => ["report\0.pdf"];
	}

	#[DataProvider('provideInvalidFileNames')]
	public function testRejectsFileName(string $fileName): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Attachment file name');

		new EmailAttachment($fileName, 'bytes', 'application/pdf');
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function provideInvalidContentTypes(): iterable
	{
		yield 'empty' => [''];
		yield 'without subtype' => ['pdf'];
		yield 'trailing line break' => ["application/pdf\n"];
		yield 'injected header' => ["text/plain\r\nBcc: someone@example.com"];
		yield 'line break in parameters' => ["text/plain; charset=utf-8\r\nBcc: someone@example.com"];
	}

	#[DataProvider('provideInvalidContentTypes')]
	public function testRejectsContentType(string $contentType): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Attachment content type');

		new EmailAttachment('report.pdf', 'bytes', $contentType);
	}

}
