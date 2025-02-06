<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Cleanup;

use WebChemistry\Emails\Section\Sections;

interface PeriodicCleaner
{

	public function cleanup(Sections $sections): void;

}
