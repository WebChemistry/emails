<?php declare(strict_types = 1);

namespace Tests;

use PHPUnit\Framework\Attributes\Before;
use Symfony\Component\EventDispatcher\EventDispatcher;
use WebChemistry\Emails\Common\Encoder;
use WebChemistry\Emails\DefaultEmailManager;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Link\BaseUrlSubscribeLinkGenerator;
use WebChemistry\Emails\Model\SoftBounceModel;
use WebChemistry\Emails\Model\SubscriptionModel;
use WebChemistry\Emails\Model\SuspensionModel;
use WebChemistry\Emails\Plugin\Inactivity\InactivityConfig;
use WebChemistry\Emails\Plugin\Inactivity\InactivityPlugin;

trait EmailManagerEnvironment
{

	use DatabaseEnvironment;
	use SectionEnvironment;

	private SoftBounceModel $softBounceModel;

	private InactivityPlugin $inactivityPlugin;

	private EmailManager $manager;

	private SubscriptionModel $subscriptionModel;

	private SuspensionModel $suspensionModel;

	private EventDispatcher $dispatcher;

	private BaseUrlSubscribeLinkGenerator $unsubscribeLinkGenerator;

	#[Before(10)]
	public function setUpWebhook(): void
	{
		$encoder = new Encoder('secret');

		$this->softBounceModel = new SoftBounceModel($this->connectionAccessor);
		$this->subscriptionModel = new SubscriptionModel($this->connectionAccessor);
		$this->unsubscribeLinkGenerator = new BaseUrlSubscribeLinkGenerator('http://example.com', $this->sections, $encoder);
		$this->suspensionModel = new SuspensionModel($this->connectionAccessor);
		$this->dispatcher = new EventDispatcher();
		$this->inactivityPlugin = new InactivityPlugin($this->connectionAccessor);
		$this->dispatcher->addSubscriber($this->inactivityPlugin);
		$this->manager = new DefaultEmailManager(
			$this->sections,
			$this->softBounceModel,
			$this->subscriptionModel,
			$this->suspensionModel,
			$this->dispatcher,
		);
	}

	/**
	 * @return object[]
	 */
	public function configureSection(string $section): array
	{
		if ($section === 'notifications') {
			return [new InactivityConfig(2)];
		}

		return [];
	}

}
