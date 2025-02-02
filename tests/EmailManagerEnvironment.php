<?php declare(strict_types = 1);

namespace Tests;

use PHPUnit\Framework\Attributes\Before;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\DefaultEmailManager;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriptionModel;
use WebChemistry\Emails\Model\SuspensionModel;
use WebChemistry\Emails\Section\Section;
use WebChemistry\Emails\Section\Sections;
use WebChemistry\Emails\Subscribe\SubscribeManager;

trait EmailManagerEnvironment
{

	use DatabaseEnvironment;
	use SectionEnvironment;

	private InactivityModel $inactivityModel;

	private SoftBounceModel $softBounceModel;

	private EmailManager $manager;

	private SubscribeManager $unsubscribeManager;

	private SubscriptionModel $subscriptionModel;

	private SuspensionModel $suspensionModel;

	#[Before(10)]
	public function setUpWebhook(): void
	{
		$this->inactivityModel = new InactivityModel(2, $this->registry);
		$this->softBounceModel = new SoftBounceModel($this->registry);
		$this->subscriptionModel = new SubscriptionModel($this->registry);
		$this->unsubscribeManager = new SubscribeManager(new Encoder('secret'));
		$this->suspensionModel = new SuspensionModel($this->registry);
		$this->manager = new DefaultEmailManager(
			$this->sections,
			$this->inactivityModel,
			$this->softBounceModel,
			$this->subscriptionModel,
			$this->suspensionModel,
			$this->unsubscribeManager,
		);
	}

}
