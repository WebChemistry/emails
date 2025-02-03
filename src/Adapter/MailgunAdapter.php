<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use InvalidArgumentException;
use Mailgun\Mailgun;
use SensitiveParameter;
use WebChemistry\Emails\HtmlMessage;
use WebChemistry\Emails\Mailer;
use WebChemistry\Emails\Message;

final readonly class MailgunAdapter extends AbstractAdapter
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

		$builder = $this->client->messages()->getBatchMessage($this->domain);

		$builder->setFromAddress($sender->email, array_filter(['full_name' => $sender->name]));
		$builder->setSubject($message->getSubject());
		$builder->setHtmlBody($message->getBody());

		$generator = $options[Mailer::UnsubscribeGeneratorOption] ?? null;

		foreach ($recipients as $recipient) {
			$variables = [];

			if ($recipient->name) {
				$variables['full_name'] = $recipient->name;
			}

			if ($generator) {
				$variables['unsubscribe_link'] = $generator($recipient->email);
			}

			$builder->addToRecipient($recipient->email, $variables);
		}

		if ($generator) {
			$builder->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
			$builder->addCustomHeader('List-Unsubscribe', '<%recipient.unsubscribe_link%>');
		}

		$builder->finalize();
	}

}
