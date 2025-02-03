<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Bridge\Nette;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use WebChemistry\Emails\Command\GenerateSecretCommand;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Confirmation\ConfirmationManager;
use WebChemistry\Emails\Connection\ConnectionAccessor;
use WebChemistry\Emails\Connection\DefaultConnectionAccessor;
use WebChemistry\Emails\DefaultEmailManager;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriptionModel;
use WebChemistry\Emails\Model\SuspensionModel;
use WebChemistry\Emails\Section\SectionBlueprint;
use WebChemistry\Emails\Section\Sections;
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
			'sections' => Expect::arrayOf(Expect::structure([
				'name' => Expect::string()->required(),
				'categories' => Expect::arrayOf(Expect::string())->default([]),
			])->castTo('array')),
		])->castTo('array');
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		/** @var mixed[] $config */
		$config = $this->getConfig();

		$builder->addDefinition($this->prefix('manager'))
			->setType(EmailManager::class)
			->setFactory(DefaultEmailManager::class);

		$builder->addDefinition($this->prefix('model.inactivity'))
			->setFactory(InactivityModel::class, [$config['inactivity']['limit']]);

		$builder->addDefinition($this->prefix('model.softBounce'))
			->setFactory(SoftBounceModel::class, ['bounceLimit' => $config['softBounce']['limit']]);

		$builder->addDefinition($this->prefix('mode.subscription'))
			->setFactory(SubscriptionModel::class);

		$builder->addDefinition($this->prefix('mode.suspension'))
			->setFactory(SuspensionModel::class);

		$builder->addDefinition($this->prefix('connectionAccessor'))
			->setType(ConnectionAccessor::class)
			->setFactory(DefaultConnectionAccessor::class);

		$sections = $builder->addDefinition($this->prefix('sections'))
			->setFactory(Sections::class);

		foreach ($config['sections'] as $section) {
			$sections->addSetup('add', [new Statement(SectionBlueprint::class, [$section['name'], $section['categories']])]);
		}

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
