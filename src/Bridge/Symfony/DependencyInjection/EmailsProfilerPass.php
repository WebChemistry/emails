<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Bridge\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use WebChemistry\Emails\Bridge\Symfony\DataCollector\EmailsDataCollector;
use WebChemistry\Emails\Bridge\Symfony\ProfilerMailerAdapter;
use WebChemistry\Emails\MailerAdapter;

final class EmailsProfilerPass implements CompilerPassInterface
{

	public function process(ContainerBuilder $container): void
	{
		if (!$container->has('profiler')) {
			return;
		}

		$container->register(EmailsDataCollector::class, EmailsDataCollector::class)
			->addTag('data_collector', [
				'template' => '@Emails/emails_collector.html.twig',
				'id' => EmailsDataCollector::Name,
			]);

		$container->register(ProfilerMailerAdapter::class, ProfilerMailerAdapter::class)
			->setDecoratedService(MailerAdapter::class)
			->setArguments([
				'$adapter' => new Reference(sprintf('%s.inner', ProfilerMailerAdapter::class)),
				'$collector' => new Reference(EmailsDataCollector::class),
			]);
	}

}
