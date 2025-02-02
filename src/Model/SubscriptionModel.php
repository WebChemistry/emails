<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Model;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\Persistence\ConnectionRegistry;
use WebChemistry\Emails\EmailManager;
use WebChemistry\Emails\Exception\UnsupportedPlatformException;
use WebChemistry\Emails\Section\SectionCategory;
use WebChemistry\Emails\Section\Sections;
use WebChemistry\Emails\Subscription\SubscriptionInfo;
use WebChemistry\Emails\Type\UnsubscribeType;

final class SubscriptionModel
{

	use ConnectionModel;
	use ManipulationModel;

	public function __construct(
		private ConnectionRegistry $registry,
		private Sections $sections,
	)
	{
	}

	/**
	 * @param array<string, bool> $values
	 */
	public function updateSectionByArrayOfBooleans(string $email, string $section, array $values): void
	{
		$this->reset($email, $section);

		$config = $this->sections->getConfig($section);

		if ($config->hasCategories()) {
			$config->validateCategories(array_keys($values));

			$unsetAll = $this->arrayAll($values, fn (bool $v): bool => !$v);

			if (!$unsetAll) {
				$categoriesToUnsubscribe = array_keys(array_filter($values, fn (bool $v): bool => !$v));

				$this->_unsubscribe([$email], UnsubscribeType::User, $section, $categoriesToUnsubscribe);

				return;
			}
		}

		$this->_unsubscribe([$email], UnsubscribeType::User, $section, [EmailManager::GlobalCategory]);
	}

	/**
	 * @param string|string[] $emails
	 */
	public function reset(string|array $emails, ?string $section = null): void
	{
		$emails = is_string($emails) ? [$emails] : $emails;

		if ($section) {
			$this->sections->validateSection($section);
		}

		$qb = $this->getConnection()->createQueryBuilder()
			->delete('email_subscriptions')
			->where('email IN(:emails)')
			->setParameter('emails', $emails, ArrayParameterType::STRING);

		if ($section) {
			$qb->andWhere('section = :section')
				->setParameter('section', $section);
		}

		$qb->executeStatement();
	}

	/**
	 * @template TKey of array-key
	 * @param array<TKey, string> $emails
	 * @return array<TKey, string>
	 */
	public function filterEmailsForDelivery(array $emails, string $section, string $category = EmailManager::GlobalCategory): array
	{
		$unsubscribed = $this->createUnsubscribedIndex($emails, $section, $category);

		return array_filter($emails, static fn ($email): bool => !isset($unsubscribed[$email]));
	}

	/**
	 * @param string[] $emails
	 * @param string $section
	 * @param string $category
	 * @return array<string, bool>
	 */
	private function createUnsubscribedIndex(array $emails, string $section, string $category = EmailManager::GlobalCategory): array
	{
		if (!$emails) {
			return [];
		}

		$section = $this->sections->getSectionCategory($section, $category);

		$qb = $this->getConnection()->createQueryBuilder()
			->select('email')
			->from('email_subscriptions')
			->where('email IN(:emails)');

		$this->addSectionCondition($qb, $section);

		$results = $qb->setParameter('emails', $emails, ArrayParameterType::STRING)
			->executeQuery();

		$index = [];

		while (($result = $results->fetchAssociative()) !== false) {
			$index[$result['email']] = true;
		}

		return $index;
	}

	public function isSubscribed(string $email, string $section, string $category = EmailManager::GlobalCategory): bool
	{
		$section = $this->sections->getSectionCategory($section, $category);

		if (!$section->unsubscribable) {
			return true;
		}

		$qb = $this->getConnection()->createQueryBuilder()
			->select('1')
			->from('email_subscriptions')
			->where('email = :email')
			->setParameter('email', $email);

		$this->addSectionCondition($qb, $section);

		return !$qb->executeQuery()->fetchOne();
	}

	public function getInfo(string $email): SubscriptionInfo
	{
		/** @var array{ section: string, category: string, type: string, created_at: string }[] $results */
		$results = $this->getConnection()->createQueryBuilder()
			->select('section, category, type, created_at')
			->from('email_subscriptions')
			->where('email = :email')
			->setParameter('email', $email)
			->executeQuery()->fetchAllAssociative();

		return SubscriptionInfo::fromResults($results, $this->sections);
	}

	public function resubscribe(string $email, string $section, string $category = EmailManager::GlobalCategory): void
	{
		$section = $this->sections->getSectionCategory($section, $category);

		$this->recordActivity($email, $section->section);

		if ($section->isGlobal()) {
			$this->reset($email, $section->section);
		} else {
			$this->getConnection()->createQueryBuilder()
				->delete('email_subscriptions')
				->where('email = :email AND section = :section AND category = :category')
				->setParameter('email', $email)
				->setParameter('section', $section->section)
				->setParameter('category', $section->category)
				->executeStatement();
		}
	}

	/**
	 * @param string|string[] $emails
	 */
	public function unsubscribe(
		string|array $emails,
		UnsubscribeType $type,
		string $section,
		string $category = EmailManager::GlobalCategory,
	): void
	{
		$sectionCategory = $this->sections->getSectionCategory($section, $category);

		if (!$sectionCategory->unsubscribable) {
			return;
		}

		$emails = is_string($emails) ? [$emails] : $emails;

		if ($sectionCategory->isGlobal() && $type === UnsubscribeType::User) {
			$this->reset($emails, $sectionCategory->section);
		} else {
			$this->recordActivity($emails, $sectionCategory->section);
		}

		$this->_unsubscribe($emails, $type, $sectionCategory->section, [$sectionCategory->category]);
	}

	/**
	 * @param string[] $emails
	 * @param string[] $categories
	 */
	private function _unsubscribe(array $emails, UnsubscribeType $type, string $section, array $categories): void
	{
		$config = $this->sections->getConfig($section);

		if (!$config->isUnsubscribable()) {
			return;
		}

		$values = [];
		$createdAt = date('Y-m-d H:i:s');

		foreach ($emails as $email) {
			foreach ($categories as $category) {
				$values[] = [
					'email' => $email,
					'type' => $type->value,
					'section' => $section,
					'category' => $category,
					'created_at' => $createdAt,
				];
			}
		}

		$this->insert('email_subscriptions', $values, ['email', 'section', 'category'], function (string $platform) use ($type): string {
			if ($platform === 'mysql') {
				if ($type === UnsubscribeType::User) {
					return 'type = new.type, created_at = new.created_at';
				} else {
					return 'created_at = new.created_at';
				}
			}

			if ($platform === 'sqlite') {
				if ($type === UnsubscribeType::User) {
					return 'type = excluded.type, created_at = excluded.created_at';
				} else {
					return 'created_at = excluded.created_at';
				}
			}

			throw new UnsupportedPlatformException($platform);
		});
	}

	private function addSectionCondition(QueryBuilder $qb, SectionCategory $section): QueryBuilder
	{
		$categories = [$section->category];

		if ($section->category !== EmailManager::GlobalCategory) {
			$categories[] = EmailManager::GlobalCategory;
		}

		$qb->andWhere('section = :section AND category IN(:categories)')
			->setParameter('section', $section->section)
			->setParameter('categories', $categories, ArrayParameterType::STRING);

		return $qb;
	}

	/**
	 * @param string|string[] $email
	 */
	public function recordActivity(string|array $email, string $section): void
	{
		$emails = is_string($email) ? [$email] : $email;

		$this->sections->validateSection($section);

		$this->getConnection()->createQueryBuilder()
			->delete('email_subscriptions')
			->where('email IN(:emails) AND section = :section AND type = :type')
			->setParameter('emails', $emails, ArrayParameterType::STRING)
			->setParameter('section', $section)
			->setParameter('type', UnsubscribeType::Inactivity->value)
			->executeStatement();
	}

	/**
	 * @template TValue
	 * @param TValue[] $value
	 * @param callable(TValue $value): bool $fn
	 */
	private function arrayAll(array $value, callable $fn): bool
	{
		foreach ($value as $v) {
			if (!$fn($v)) {
				return false;
			}
		}

		return true;
	}

}
