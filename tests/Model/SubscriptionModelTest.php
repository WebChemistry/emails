<?php declare(strict_types = 1);

namespace Tests\Model;

use Tests\DatabaseEnvironment;
use Tests\SectionEnvironment;
use Tests\TestCase;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Model\SubscriptionModel;
use WebChemistry\Emails\Type\UnsubscribeType;

final class SubscriptionModelTest extends TestCase
{

	use DatabaseEnvironment;
	use SectionEnvironment;

	private SubscriptionModel $model;

	protected function setUp(): void
	{
		$this->model = new SubscriptionModel($this->connectionAccessor);
	}

	public function testInitialState(): void
	{
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, $this->sections->getEssentialCategory()));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'article')));
		$this->assertSame([
			'article' => true,
			'comment' => true,
			'mention' => true,
		], $this->model->getInfo($this->firstEmail, $this->sections->getSection('notifications'))->getCategoriesAsMapOfBooleans());
	}

	public function testUnsubscribeEssential(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, $this->sections->getEssentialCategory());

		$this->assertTrue($this->model->isSubscribed($this->firstEmail, $this->sections->getEssentialCategory()));
	}

	public function testUnsubscribeNotification(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, $this->sections->getCategory('notifications', 'article'));

		$this->assertFalse($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'article')));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'comment')));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, $this->sections->getEssentialCategory()));

		$this->assertSame([
			'article' => false,
			'comment' => true,
			'mention' => true,
		], $this->model->getInfo($this->firstEmail, $this->sections->getSection('notifications'))->getCategoriesAsMapOfBooleans());

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
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, $this->sections->getCategory('notifications'));

		$this->assertFalse($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'article')));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'comment')));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'mention')));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications')));

		$this->assertTrue($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('section')));
		$this->assertSame(UnsubscribeType::User, $this->model->getInfo($this->firstEmail, $this->sections->getSection('notifications'))->getReason());
		$this->assertSame(UnsubscribeType::User, $this->model->getInfo($this->firstEmail, $this->sections->getSection('notifications'))->getReason('article'));

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
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, $this->sections->getCategory('notifications', 'article'));
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, $this->sections->getCategory('notifications', 'comment'));

		$this->model->resubscribe($this->firstEmail, $this->sections->getCategory('notifications'));

		$this->assertSame([], $this->databaseSnapshot());
	}

	public function testResubscribeOneCategory(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, $this->sections->getCategory('notifications', 'article'));
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, $this->sections->getCategory('notifications', 'comment'));

		$this->model->resubscribe($this->firstEmail, $this->sections->getCategory('notifications', 'comment'));

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
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, $this->sections->getCategory('notifications', 'article'));
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, $this->sections->getCategory('notifications'));

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
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, $this->sections->getCategory('notifications', 'article'));
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::Inactivity, $this->sections->getCategory('notifications'));

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
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::Inactivity, $this->sections->getCategory('notifications', 'article'));

		$this->assertSame(UnsubscribeType::Inactivity, $this->model->getInfo($this->firstEmail, $this->sections->getSection('notifications'))->getReason('article'));

		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, $this->sections->getCategory('notifications', 'article'));

		$this->assertSame(UnsubscribeType::User, $this->model->getInfo($this->firstEmail, $this->sections->getSection('notifications'))->getReason('article'));
	}

	public function testDowngrade(): void
	{
		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::User, $this->sections->getCategory('notifications', 'article'));

		$this->assertSame(UnsubscribeType::User, $this->model->getInfo($this->firstEmail, $this->sections->getSection('notifications'))->getReason('article'));

		$this->model->unsubscribe($this->firstEmail, UnsubscribeType::Inactivity, $this->sections->getCategory('notifications', 'article'));

		$this->assertSame(UnsubscribeType::User, $this->model->getInfo($this->firstEmail, $this->sections->getSection('notifications'))->getReason('article'));
	}

	public function testUpdateSomeByArrayOfBooleans(): void
	{
		$this->model->updateSectionByArrayOfBooleans($this->firstEmail, $this->sections->getSection('notifications'), [
			'article' => false,
			'comment' => true,
			'mention' => false,
		]);

		$this->assertFalse($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'article')));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'comment')));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'mention')));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications')));

		$this->assertSame([
			'article' => false,
			'comment' => true,
			'mention' => false,
		], $this->model->getInfo($this->firstEmail, $this->sections->getSection('notifications'))->getCategoriesAsMapOfBooleans());

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
		$this->model->updateSectionByArrayOfBooleans($this->firstEmail, $this->sections->getSection('notifications'), [
			'article' => false,
			'comment' => false,
			'mention' => false,
		]);

		$this->assertFalse($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'article')));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'comment')));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'mention')));
		$this->assertFalse($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications')));

		$this->assertSame([
			'article' => false,
			'comment' => false,
			'mention' => false,
		], $this->model->getInfo($this->firstEmail, $this->sections->getSection('notifications'))->getCategoriesAsMapOfBooleans());

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
		$this->model->updateSectionByArrayOfBooleans($this->firstEmail, $this->sections->getSection('notifications'), [
			'article' => true,
			'comment' => true,
			'mention' => true,
		]);

		$this->assertTrue($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'article')));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'comment')));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications', 'mention')));
		$this->assertTrue($this->model->isSubscribed($this->firstEmail, $this->sections->getCategory('notifications')));

		$this->assertSame([
			'article' => true,
			'comment' => true,
			'mention' => true,
		], $this->model->getInfo($this->firstEmail, $this->sections->getSection('notifications'))->getCategoriesAsMapOfBooleans());
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
