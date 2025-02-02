<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Bridge\Nette;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use WebChemistry\Emails\Command\GenerateSecretCommand;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Confirmation\ConfirmationManager;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Subscribe\SubscribeManager;

final class EmailsExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'encoder' => Expect::structure([
				'secret' => Expect::string()->dynamic(),
			])->castTo('array'),
			'inactivity' => Expect::structure([
				'limit' => Expect::int()->required(),
			])->required()->castTo('array'),
			'softBounce' => Expect::structure([
				'limit' => Expect::int(3),
			])->castTo('array'),
		])->castTo('array');
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var mixed[] $config */
		$config = $this->getConfig();

		$builder->addDefinition($this->prefix('manager'))
			->setFactory(EmailManager::class);

		$builder->addDefinition($this->prefix('model.inactivity'))
			->setFactory(InactivityModel::class, [$config['inactivity']['limit']]);

		$builder->addDefinition($this->prefix('model.softBounce'))
			->setFactory(SoftBounceModel::class, ['bounceLimit' => $config['softBounce']['limit']]);

//		$builder->addDefinition($this->prefix('model.subscriber'))
//			->setFactory(SubscriberModel::class);

		if ($config['encoder']['secret']) {
			$builder->addDefinition($this->prefix('encoder'))
				->setFactory(Encoder::class, [$config['encoder']['secret']]);

			$builder->addDefinition($this->prefix('manager.unsubscribe'))
				->setFactory(SubscribeManager::class);

			$builder->addDefinition($this->prefix('manager.confirmation'))
				->setFactory(ConfirmationManager::class);
		}

		$builder->addDefinition($this->prefix('command'))
			->setFactory(GenerateSecretCommand::class);
	}

}
