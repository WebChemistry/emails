<?php declare(strict_types = 1);

namespace WebChemistry\Emails\Model;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\Persistence\ConnectionRegistry;
use WebChemistry\Emails\Connection\ConnectionAccessor;
use WebChemistry\Emails\Exception\UnsupportedPlatformException;
use WebChemistry\Emails\Section\Section;
use WebChemistry\Emails\Section\SectionCategory;
use WebChemistry\Emails\Subscription\SubscriptionInfo;
use WebChemistry\Emails\Type\UnsubscribeType;

final readonly class SubscriptionModel
{

	use ManipulationModel;

	public function __construct(
		private ConnectionAccessor $connectionAccessor,
	)
	{
	}

	/**
	 * @param array<string, bool> $values
	 */
	public function updateSectionByArrayOfBooleans(string $email, Section $section, array $values): void
	{
		$unsetAll = $this->arrayAll($values, fn (bool $v): bool => !$v);

		if (!$unsetAll) {
			$categoriesToUnsubscribe = array_keys(array_filter($values, fn (bool $v): bool => !$v));
		} else {
			$categoriesToUnsubscribe = [SectionCategory::Global];
		}

		$this->reset($email, $section);

		$this->_unsubscribe([$email], UnsubscribeType::User, $section, $categoriesToUnsubscribe);
	}

	/**
	 * @param string|string[] $emails
	 */
	public function reset(string|array $emails, ?Section $section = null): void
	{
		$emails = is_string($emails) ? [$emails] : $emails;

		$qb = $this->connectionAccessor->get()->createQueryBuilder()
			->delete('email_subscriptions')
			->where('email IN(:emails)')
			->setParameter('emails', $emails, ArrayParameterType::STRING);

		if ($section) {
			$qb->andWhere('section = :section')
				->setParameter('section', $section->name);
		}

		$qb->executeStatement();
	}

	/**
	 * @template TKey of array-key
	 * @param array<TKey, string> $emails
	 * @return array<TKey, string>
	 */
	public function filterEmailsForDelivery(array $emails, SectionCategory $category): array
	{
		$unsubscribed = $this->createUnsubscribedIndex($emails, $category);

		return array_filter($emails, static fn ($email): bool => !isset($unsubscribed[$email]));
	}

	/**
	 * @param string[] $emails
	 * @return array<string, bool>
	 */
	private function createUnsubscribedIndex(array $emails, SectionCategory $category): array
	{
		if (!$emails) {
			return [];
		}

		$qb = $this->connectionAccessor->get()->createQueryBuilder()
			->select('email')
			->from('email_subscriptions')
			->where('email IN(:emails)');

		$this->addSectionCondition($qb, $category);

		$results = $qb->setParameter('emails', $emails, ArrayParameterType::STRING)
			->executeQuery();

		$index = [];

		while (($result = $results->fetchAssociative()) !== false) {
			$index[$result['email']] = true;
		}

		return $index;
	}

	public function isSubscribed(string $email, SectionCategory $category): bool
	{
		if (!$category->isUnsubscribable()) {
			return true;
		}

		$qb = $this->connectionAccessor->get()->createQueryBuilder()
			->select('1')
			->from('email_subscriptions')
			->where('email = :email')
			->setParameter('email', $email);

		$this->addSectionCondition($qb, $category);

		return !$qb->executeQuery()->fetchOne();
	}

	public function getInfo(string $email, Section $section): SubscriptionInfo
	{
		/** @var array{ section: string, category: string, type: string, created_at: string }[] $results */
		$results = $this->connectionAccessor->get()->createQueryBuilder()
			->select('section, category, type, created_at')
			->from('email_subscriptions')
			->where('email = :email')
			->andWhere('section = :section')
			->setParameter('section', $section->name)
			->setParameter('email', $email)
			->executeQuery()->fetchAllAssociative();

		return SubscriptionInfo::fromResults($results, $section);
	}

	public function resubscribe(string $email, SectionCategory $category): void
	{
		$this->recordActivity($email, $category->section);

		if ($category->isGlobal()) {
			$this->reset($email, $category->section);
		} else {
			$this->connectionAccessor->get()->createQueryBuilder()
				->delete('email_subscriptions')
				->where('email = :email AND section = :section AND category = :category')
				->setParameter('email', $email)
				->setParameter('section', $category->section->name)
				->setParameter('category', $category->name)
				->executeStatement();
		}
	}

	/**
	 * @param string|string[] $emails
	 */
	public function unsubscribe(string|array $emails, UnsubscribeType $type, SectionCategory $category): void
	{
		if (!$category->isUnsubscribable()) {
			return;
		}

		$emails = is_string($emails) ? [$emails] : $emails;

		if ($category->isGlobal() && $type === UnsubscribeType::User) {
			$this->reset($emails, $category->section);
		} else {
			$this->recordActivity($emails, $category->section);
		}

		$this->_unsubscribe($emails, $type, $category->section, [$category->name]);
	}

	/**
	 * @param string[] $emails
	 * @param string[] $categories
	 */
	private function _unsubscribe(array $emails, UnsubscribeType $type, Section $section, array $categories): void
	{
		if (!$section->isUnsubscribable()) {
			return;
		}

		$values = [];
		$createdAt = date('Y-m-d H:i:s');

		foreach ($emails as $email) {
			foreach ($categories as $category) {
				$values[] = [
					'email' => $email,
					'type' => $type->value,
					'section' => $section->name,
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

	private function addSectionCondition(QueryBuilder $qb, SectionCategory $category): QueryBuilder
	{
		$categories = [$category->name];

		if (!$category->isGlobal()) {
			$categories[] = $category::Global;
		}

		$qb->andWhere('section = :section AND category IN(:categories)')
			->setParameter('section', $category->section->name)
			->setParameter('categories', $categories, ArrayParameterType::STRING);

		return $qb;
	}

	/**
	 * @param string|string[] $email
	 */
	public function recordActivity(string|array $email, Section $section): void
	{
		$emails = is_string($email) ? [$email] : $email;

		$this->connectionAccessor->get()->createQueryBuilder()
			->delete('email_subscriptions')
			->where('email IN(:emails) AND section = :section AND type = :type')
			->setParameter('emails', $emails, ArrayParameterType::STRING)
			->setParameter('section', $section->name)
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
