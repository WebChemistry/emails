<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Bridge\Symfony;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use WebChemistry\Emails\Adapter\CompoundAdapter;
use WebChemistry\Emails\Adapter\NeverAdapter;
use WebChemistry\Emails\Cleanup\PeriodicCleaner;
use WebChemistry\Emails\Command\GenerateSecretCommand;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\Confirmation\ConfirmationManager;
use WebChemistry\Emails\Confirmation\DefaultConfirmationManager;
use WebChemistry\Emails\Connection\ConnectionAccessor;
use WebChemistry\Emails\Connection\DefaultConnectionAccessor;
use WebChemistry\Emails\DefaultEmailManager;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Mailer;
use WebChemistry\Emails\Mailer\ManagedMailer;
use WebChemistry\Emails\MailerAdapter;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriptionModel;
use WebChemistry\Emails\Model\SuspensionModel;
use WebChemistry\Emails\Section\SectionBlueprint;
use WebChemistry\Emails\Section\Sections;
use WebChemistry\Emails\Validator\CompoundEmailValidator;
use WebChemistry\Emails\Validator\EmailValidator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

final class EmailsBundle extends AbstractBundle
{

	public function configure(DefinitionConfigurator $definition): void
	{
		$definition->rootNode()
			->children()
				->arrayNode('encoder')
					->children()
						->scalarNode('secret')->defaultNull()->end()
					->end()
				->end()
				->arrayNode('inactivity')
					->isRequired()
					->children()
						->integerNode('limit')->isRequired()->end()
					->end()
				->end()
				->arrayNode('soft_bounce')
					->addDefaultsIfNotSet()
					->children()
						->integerNode('limit')->defaultValue(3)->end()
					->end()
				->end()
				->scalarNode('connection')->defaultNull()->end()
				->arrayNode('adapter')
					->addDefaultsIfNotSet()
					->children()
						->scalarNode('transactional')->defaultNull()->end()
						->scalarNode('marketing')->defaultNull()->end()
					->end()
				->end()
				->arrayNode('sections')
					->arrayPrototype()
						->children()
							->scalarNode('name')->isRequired()->end()
							->arrayNode('categories')
								->scalarPrototype()->end()
								->defaultValue([])
							->end()
							->booleanNode('unsubscribable')->defaultTrue()->end()
							->arrayNode('configs')
								->arrayPrototype()
									->children()
										->scalarNode('class')->isRequired()->end()
										->arrayNode('arguments')
											->scalarPrototype()->end()
											->defaultValue([])
										->end()
									->end()
								->end()
								->defaultValue([])
							->end()
						->end()
					->end()
				->end()
			->end()
		;
	}

	/**
	 * @param mixed[] $config
	 */
	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
	{
		$services = $container->services();

		$services->set(Sections::class);

		$services->set(InactivityModel::class)
			->args([
				'$maxInactivity' => $config['inactivity']['limit'],
			])
			->autowire();

		$services->set(SoftBounceModel::class)
			->args([
				'$bounceLimit' => $config['soft_bounce']['limit'],
			])
			->autowire();

		$services->set(SubscriptionModel::class)
			->autowire();

		$services->set(SuspensionModel::class)
			->autowire();

		$services->set(ConnectionAccessor::class, DefaultConnectionAccessor::class)
			->args([
				'$connectionName' => $config['connection'],
			])
			->autowire();

		$services->set(DefaultEmailManager::class)
			->args([
				'$cleaners' => tagged_iterator('emails.periodic_cleaner'),
			])
			->autowire();

		$services->alias(EmailManager::class, DefaultEmailManager::class);

		$this->registerSections($config['sections'], $builder);
		$this->registerAdapter($config['adapter'], $container);
		$this->registerValidator($container);
		$this->registerMailer($container);

		if (($config['encoder']['secret'] ?? null) !== null) {
			$services->set(Encoder::class)
				->args([
					'$secret' => $config['encoder']['secret'],
				]);

			$services->set(DefaultConfirmationManager::class)
				->autowire();

			$services->alias(ConfirmationManager::class, DefaultConfirmationManager::class);
		}

		$services->set(GenerateSecretCommand::class)
			->tag('console.command');

		$builder->registerForAutoconfiguration(PeriodicCleaner::class)
			->addTag('emails.periodic_cleaner');

		$builder->registerForAutoconfiguration(EmailValidator::class)
			->addTag('emails.email_validator');
	}

	/**
	 * @param array{transactional: non-empty-string|null, marketing: non-empty-string|null} $adapterConfig
	 */
	private function registerAdapter(array $adapterConfig, ContainerConfigurator $container): void
	{
		$services = $container->services();

		$transactional = $adapterConfig['transactional'];
		$marketing = $adapterConfig['marketing'];

		if ($transactional !== null && $marketing !== null) {
			$services->set(CompoundAdapter::class)
				->args([
					'$transactional' => new Reference($transactional),
					'$marketing' => new Reference($marketing),
				]);

			$services->alias(MailerAdapter::class, CompoundAdapter::class);

			return;
		}

		if ($transactional !== null) {
			$services->alias(MailerAdapter::class, $transactional);

			return;
		}

		if ($marketing !== null) {
			$services->alias(MailerAdapter::class, $marketing);

			return;
		}

		$services->set(NeverAdapter::class);
		$services->alias(MailerAdapter::class, NeverAdapter::class);
	}

	private function registerValidator(ContainerConfigurator $container): void
	{
		$container->services()
			->set(CompoundEmailValidator::class)
			->args([
				'$validators' => tagged_iterator('emails.email_validator'),
			]);

		$container->services()
			->alias(EmailValidator::class, CompoundEmailValidator::class);
	}

	private function registerMailer(ContainerConfigurator $container): void
	{
		$container->services()
			->set(ManagedMailer::class)
			->autowire();

		$container->services()
			->alias(Mailer::class, ManagedMailer::class);
	}

	/**
	 * @param list<array{name: non-empty-string, categories: list<string>, unsubscribable: bool, configs: list<array{class: class-string, arguments: list<string>}>}> $sections
	 */
	private function registerSections(array $sections, ContainerBuilder $builder): void
	{
		$sectionsDefinition = $builder->getDefinition(Sections::class);

		foreach ($sections as $section) {
			$configs = [];

			foreach ($section['configs'] as $configItem) {
				$configs[] = new Definition($configItem['class'], $configItem['arguments']);
			}

			$blueprint = new Definition(SectionBlueprint::class, [
				'$name' => $section['name'],
				'$categories' => $section['categories'],
				'$unsubscribable' => $section['unsubscribable'],
				'$configs' => $configs,
			]);

			$sectionsDefinition->addMethodCall('add', [$blueprint]);
		}
	}

}
