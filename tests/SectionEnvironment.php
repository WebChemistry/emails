<?php declare(strict_types = 1);

namespace Tests;

use PHPUnit\Framework\Attributes\Before;
use WebChemistry\Emails\Section\SectionBlueprint;
use WebChemistry\Emails\Section\Sections;

trait SectionEnvironment
{

	private Sections $sections;

	#[Before(15)]
	protected function setUpSections(): void
	{
		$this->sections = new Sections();
		$this->sections->add(new SectionBlueprint('notifications', [
			'article',
			'comment',
			'mention',
		], unsubscribeAllCategories: false));
		$this->sections->add(new SectionBlueprint('section', ['category']));
	}

}
