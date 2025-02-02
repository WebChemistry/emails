<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Event;

use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\EmailRegistry;
use WebChemistry\Emails\Section\SectionCategory;

final readonly class BeforeEmailSentEvent
{

	public function __construct(
		public EmailManager $emailManager,
		public EmailRegistry $registry,
		public SectionCategory $category,
	)
	{
	}

}
