<?php declare(strict_types = 1);

namespace Tests;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class PhpUnitExtension implements Extension
{

	public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
	{
		if ($configuration->noOutput()) {
			return;
		}

		$facade->registerSubscriber(new PhpUnitSubscriber());
	}

}
