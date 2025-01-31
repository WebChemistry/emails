<?php declare(strict_types = 1);

namespace Tests;

use PHPUnit\Framework\Attributes\Before;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriberModel;
use WebChemistry\Emails\Subscribe\SubscribeManager;

trait EmailManagerEnvironment
{

	use DatabaseEnvironment;

	private SubscriberModel $subscriberModel;

	private InactivityModel $inactivityModel;

	private SoftBounceModel $softBounceModel;

	private EmailManager $manager;

	private SubscribeManager $unsubscribeManager;

	#[Before(10)]
	public function setUpWebhook(): void
	{
		$this->subscriberModel = new SubscriberModel($this->registry);
		$this->inactivityModel = new InactivityModel(2, $this->registry, $this->subscriberModel);
		$this->softBounceModel = new SoftBounceModel($this->registry, $this->subscriberModel);
		$this->unsubscribeManager = new SubscribeManager(new Encoder('secret'));
		$this->manager = new EmailManager(
			$this->inactivityModel,
			$this->subscriberModel,
			$this->softBounceModel,
			$this->unsubscribeManager,
		);
	}

}
