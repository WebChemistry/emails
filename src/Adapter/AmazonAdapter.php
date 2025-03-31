<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Adapter;

use Aws\Credentials\Credentials;
use Aws\SesV2\SesV2Client;
use InvalidArgumentException;
use SensitiveParameter;
use WebChemistry\Emails\EmailAccount;
use WebChemistry\Emails\HtmlMessage;
use WebChemistry\Emails\Message;

final readonly class AmazonAdapter extends AbstractAdapter
{

	private SesV2Client $client;

	public function __construct(
		#[SensitiveParameter]
		string $region,
		#[SensitiveParameter]
		?string $accessKey = null,
		#[SensitiveParameter]
		?string $secretKey = null,
		#[SensitiveParameter]
		?string $token = null,
	)
	{
		$options = [
			'region' => $region,
		];

		if ($accessKey !== null || $secretKey !== null) {
			if (!$accessKey || !$secretKey) {
				throw new InvalidArgumentException('Access Key and Secret Key must be both set or both null.');
			}

			$options['credentials'] = new Credentials($accessKey, $secretKey, $token);
		}

		$this->client = new SesV2Client($options);
	}

	public function send(array $recipients, Message $message, array $options = []): void
	{
		if (!$message instanceof HtmlMessage) {
			throw new InvalidArgumentException(sprintf('Message must be instance of %s.', HtmlMessage::class));
		}

		$this->client->sendEmail([
			'Destination' => [
				'ToAddresses' => array_map(fn (EmailAccount $recipient): string => $recipient->email, $recipients),
			],
			'FromEmailAddress' => $message->getSender()->toString(),
			'Content' => [
				'Simple' => [
					'Subject' => [
						'Charset' => 'UTF-8',
						'Data' => $message->getSubject(),
					],
					'Body' => [
						'Html' => [
							'Charset' => 'UTF-8',
							'Data' => $message->getBody(),
						],
					],
				],
			],
		]);
	}

}
