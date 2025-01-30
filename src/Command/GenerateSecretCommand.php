<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('email:generate-secret')]
final class GenerateSecretCommand extends Command
{

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln(base64_encode(openssl_random_pseudo_bytes(32)));

		return self::SUCCESS;
	}

}
