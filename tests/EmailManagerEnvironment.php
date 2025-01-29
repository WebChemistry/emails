<?php declare(strict_types = 1);

namespace Tests;

use PHPUnit\Framework\Attributes\Before;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriberModel;
use WebChemistry\Emails\Unsubscribe\UnsubscribeEncoder;
use WebChemistry\Emails\Unsubscribe\UnsubscribeManager;

trait EmailManagerEnvironment
{

	use DatabaseEnvironment;

	private SubscriberModel $subscriberModel;

	private InactivityModel $inactivityModel;

	private SoftBounceModel $softBounceModel;

	private EmailManager $manager;

	private UnsubscribeManager $unsubscribeManager;

	#[Before(10)]
	public function setUpWebhook(): void
	{
		$this->subscriberModel = new SubscriberModel($this->registry);
		$this->inactivityModel = new InactivityModel(2, $this->registry, $this->subscriberModel);
		$this->softBounceModel = new SoftBounceModel($this->registry, $this->subscriberModel);
		$this->unsubscribeManager = new UnsubscribeManager(new UnsubscribeEncoder('secret'));
		$this->manager = new EmailManager(
			$this->inactivityModel,
			$this->subscriberModel,
			$this->softBounceModel,
			$this->unsubscribeManager,
		);
	}

}
