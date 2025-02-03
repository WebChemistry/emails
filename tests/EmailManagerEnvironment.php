<?php declare(strict_types = 1);

namespace Tests;

use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\EventDispatcher\EventDispatcher;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\DefaultEmailManager;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Link\BaseUrlSubscribeLinkGenerator;
use WebChemistry\Emails\Model\InactivityModel;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriptionModel;
use WebChemistry\Emails\Model\SuspensionModel;

trait EmailManagerEnvironment
{

	use DatabaseEnvironment;
	use SectionEnvironment;

	private InactivityModel $inactivityModel;

	private SoftBounceModel $softBounceModel;

	private EmailManager $manager;

	private SubscriptionModel $subscriptionModel;

	private SuspensionModel $suspensionModel;

	private EventDispatcher $dispatcher;

	private BaseUrlSubscribeLinkGenerator $unsubscribeLinkGenerator;

	#[Before(10)]
	public function setUpWebhook(): void
	{
		$encoder = new Encoder('secret');

		$this->inactivityModel = new InactivityModel(2, $this->connectionAccessor);
		$this->softBounceModel = new SoftBounceModel($this->connectionAccessor);
		$this->subscriptionModel = new SubscriptionModel($this->connectionAccessor);
		$this->unsubscribeLinkGenerator = new BaseUrlSubscribeLinkGenerator('http://example.com', $this->sections, $encoder);
		$this->suspensionModel = new SuspensionModel($this->connectionAccessor);
		$this->dispatcher = new EventDispatcher();
		$this->manager = new DefaultEmailManager(
			$this->sections,
			$this->inactivityModel,
			$this->softBounceModel,
			$this->subscriptionModel,
			$this->suspensionModel,
			$this->dispatcher,
		);
	}

}
