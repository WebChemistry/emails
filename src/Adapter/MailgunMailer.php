<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use InvalidArgumentException;
use Mailgun\Mailgun;
use Mailgun\Message\MessageBuilder;
use SensitiveParameter;
use WebChemistry\Emails\HtmlMessage;
use WebChemistry\Emails\Message;

final readonly class MailgunMailer extends AbstractMailer
{

	private Mailgun $client;

	public function __construct(
		#[SensitiveParameter]
		string $apiKey,
		private string $domain,
		?string $endpoint = null,
	)
	{
		$this->client = $endpoint ? Mailgun::create($apiKey, $endpoint) : Mailgun::create($apiKey);
	}

	public function send(array $recipients, Message $message, array $options = []): void
	{
		if (!$message instanceof HtmlMessage) {
			throw new InvalidArgumentException(sprintf('Message must be instance of %s.', HtmlMessage::class));
		}

		$sender = $message->getSender();

		$builder = new MessageBuilder();

		$builder->setFromAddress($sender->email, array_filter(['full_name' => $sender->name]));

		foreach ($recipients as $recipient) {
			$variables = [];

			if ($recipient->name) {
				$variables['full_name'] = $recipient->name;
			}

			$builder->addToRecipient($recipient->email, $variables);
		}

		$builder->setSubject($message->getSubject());

		$builder->setHtmlBody($message->getBody());

		$this->client->messages()->send($this->domain, $builder->getMessage());
	}

}
