<?php declare(strict_types = 1);

namespace WebChemistry\Emails;

use InvalidArgumentException;

/**
 * A file sent with an html message, held in memory: its name as the recipient sees it, its bytes and its type.
 */
final readonly class EmailAttachment
{

	/**
	 * @param non-empty-string $fileName
	 * @param non-empty-string $contentType
	 */
	public function __construct(
		public string $fileName,
		public string $content,
		public string $contentType,
	)
	{
		// The name ends up quoted inside a MIME header, where a quote or a backslash closes it early
		// and a line break starts a header of its own.
		if (preg_match('/^[^"\\\\\x00-\x1F\x7F]+$/D', $fileName) !== 1) {
			throw new InvalidArgumentException('Attachment file name must be non-empty and free of quotes, backslashes and control characters.');
		}

		if (preg_match('~^[A-Za-z0-9][A-Za-z0-9!#$&^_.+-]*/[A-Za-z0-9][A-Za-z0-9!#$&^_.+-]*(?:[ \t]*;[^\x00-\x1F\x7F]*)?$~D', $contentType) !== 1) {
			throw new InvalidArgumentException('Attachment content type must be "type/subtype", optionally followed by parameters, without control characters.');
		}
	}

}
