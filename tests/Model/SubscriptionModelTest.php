<?php declare(strict_types = 1);

namespace Tests\Model;

use Tests\DatabaseEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Model\SubscriptionModel;
use WebChemistry\Emails\Section\SectionConfig;
use WebChemistry\Emails\Section\Sections;
use WebChemistry\Emails\Type\UnsubscribeType;

final class SubscriptionModelTest extends TestCase
{

	use DatabaseEnvironment;

	private SubscriptionModel $model;

	protected function setUp(): void
	{
		$sections = new Sections();
		$sections->addSection(new SectionConfig('notifications', [
			'article',
			'comment',
			'mention',
		]));
		$sections->addSection(new SectionConfig('section', ['category']));

		$this->model = new SubscriptionModel($this->registry, $sections);
	}

	public function testInitialState(): void
	{
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, EmailManager::SectionEssential));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, 'notifications', 'article'));
		$this->assertSame([
			'article' => true,
			'comment' => true,
			'mention' => true,
		], $this->model->getInfo($this->firstEmail)->getCategoriesAsMapOfBooleans('notifications'));
	}

	public function testUnsubscribeEssential(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, EmailManager::SectionEssential);

		$this->assertTrue($this->model->isSubscribed($this->firstEmail, EmailManager::SectionEssential));
	}

	public function testUnsubscribeNotification(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, 'notifications', 'article');

		$this->assertFalse($this->model->isSubscribed($this->firstEmail, 'notifications', 'article'));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, 'notifications', 'comment'));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, EmailManager::SectionEssential));

		$this->assertSame([
			'article' => false,
			'comment' => true,
			'mention' => true,
		], $this->model->getInfo($this->firstEmail)->getCategoriesAsMapOfBooleans('notifications'));

		$this->assertSame([
			[
				'email' => $this->firstEmail,
				'section' => 'notifications',
				'type' => UnsubscribeType::User->value,
				'category' => 'article',
			],
		], $this->databaseSnapshot());
	}

	public function testUnsubscribeGlobal(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, 'notifications');

		$this->assertFalse($this->model->isSubscribed($this->firstEmail, 'notifications', 'article'));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, 'notifications', 'comment'));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, 'notifications', 'mention'));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, 'notifications'));

		$this->assertTrue($this->model->isSubscribed($this->firstEmail, 'section'));
		$this->assertSame(UnsubscribeType::User, $this->model->getInfo($this->firstEmail)->getReason('notifications'));
		$this->assertSame(UnsubscribeType::User, $this->model->getInfo($this->firstEmail)->getReason('notifications', 'article'));

		$this->assertSame([
			[
				'email' => $this->firstEmail,
				'section' => 'notifications',
				'type' => UnsubscribeType::User->value,
				'category' => EmailManager::GlobalCategory,
			]
		], $this->databaseSnapshot());
	}

	public function testResubscribeGlobal(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, 'notifications', 'article');
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, 'notifications', 'comment');

		$this->model->resubscribe($this->firstEmail, 'notifications');

		$this->assertSame([], $this->databaseSnapshot());
	}

	public function testResubscribeOneCategory(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, 'notifications', 'article');
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, 'notifications', 'comment');

		$this->model->resubscribe($this->firstEmail, 'notifications', 'comment');

		$this->assertSame([
			[
				'email' => $this->firstEmail,
				'section' => 'notifications',
				'type' => UnsubscribeType::User->value,
				'category' => 'article',
			],
		], $this->databaseSnapshot());
	}

	public function testUnsubscribeOneThenUnsubscribeGlobal(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, 'notifications', 'article');
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, 'notifications');

		$this->assertSame([
			[
				'email' => $this->firstEmail,
				'section' => 'notifications',
				'type' => UnsubscribeType::User->value,
				'category' => EmailManager::GlobalCategory,
			]
		], $this->databaseSnapshot());
	}

	public function testUnsubscribeOneThenUnsubscribeGlobalWithInactivityType(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, 'notifications', 'article');
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::Inactivity, 'notifications');

		$this->assertSame([
			[
				'email' => $this->firstEmail,
				'section' => 'notifications',
				'type' => UnsubscribeType::User->value,
				'category' => 'article',
			],
			[
				'email' => $this->firstEmail,
				'section' => 'notifications',
				'type' => UnsubscribeType::Inactivity->value,
				'category' => EmailManager::GlobalCategory,
			],
		], $this->databaseSnapshot());
	}

	public function testUpgrade(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::Inactivity, 'notifications', 'article');

		$this->assertSame(UnsubscribeType::Inactivity, $this->model->getInfo($this->firstEmail)->getReason('notifications', 'article'));

		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, 'notifications', 'article');

		$this->assertSame(UnsubscribeType::User, $this->model->getInfo($this->firstEmail)->getReason('notifications', 'article'));
	}

	public function testDowngrade(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, 'notifications', 'article');

		$this->assertSame(UnsubscribeType::User, $this->model->getInfo($this->firstEmail)->getReason('notifications', 'article'));

		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::Inactivity, 'notifications', 'article');

		$this->assertSame(UnsubscribeType::User, $this->model->getInfo($this->firstEmail)->getReason('notifications', 'article'));
	}

	public function testUpdateSomeByArrayOfBooleans(): void
	{
		$this->model->updateSectionByArrayOfBooleans($this->firstEmail, 'notifications', [
			'article' => false,
			'comment' => true,
			'mention' => false,
		]);

		$this->assertFalse($this->model->isSubscribed($this->firstEmail, 'notifications', 'article'));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, 'notifications', 'comment'));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, 'notifications', 'mention'));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, 'notifications'));

		$this->assertSame([
			'article' => false,
			'comment' => true,
			'mention' => false,
		], $this->model->getInfo($this->firstEmail)->getCategoriesAsMapOfBooleans('notifications'));

		$this->assertSame([
			[
				'email' => $this->firstEmail,
				'section' => 'notifications',
				'type' => UnsubscribeType::User->value,
				'category' => 'article',
			],
			[
				'email' => $this->firstEmail,
				'section' => 'notifications',
				'type' => UnsubscribeType::User->value,
				'category' => 'mention',
			],
		], $this->databaseSnapshot());
	}

	public function testUpdateAllByArrayOfBooleans(): void
	{
		$this->model->updateSectionByArrayOfBooleans($this->firstEmail, 'notifications', [
			'article' => false,
			'comment' => false,
			'mention' => false,
		]);

		$this->assertFalse($this->model->isSubscribed($this->firstEmail, 'notifications', 'article'));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, 'notifications', 'comment'));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, 'notifications', 'mention'));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, 'notifications'));

		$this->assertSame([
			'article' => false,
			'comment' => false,
			'mention' => false,
		], $this->model->getInfo($this->firstEmail)->getCategoriesAsMapOfBooleans('notifications'));

		$this->assertSame([
			[
				'email' => $this->firstEmail,
				'section' => 'notifications',
				'type' => UnsubscribeType::User->value,
				'category' => EmailManager::GlobalCategory,
			]
		], $this->databaseSnapshot());
	}

	public function testUpdateNoneByArrayOfBooleans(): void
	{
		$this->model->updateSectionByArrayOfBooleans($this->firstEmail, 'notifications', [
			'article' => true,
			'comment' => true,
			'mention' => true,
		]);

		$this->assertTrue($this->model->isSubscribed($this->firstEmail, 'notifications', 'article'));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, 'notifications', 'comment'));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, 'notifications', 'mention'));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, 'notifications'));

		$this->assertSame([
			'article' => true,
			'comment' => true,
			'mention' => true,
		], $this->model->getInfo($this->firstEmail)->getCategoriesAsMapOfBooleans('notifications'));
	}

	/**
	 * @return array{ email: string, section: string, type: string, category: string }[]
	 */
	private function databaseSnapshot(): array
	{
		return $this->connection->createQueryBuilder()
			->select('email, section, type, category')
			->from('email_subscriptions')
			->orderBy('created_at', 'ASC')
			->executeQuery()->fetchAllAssociative();
	}

}
