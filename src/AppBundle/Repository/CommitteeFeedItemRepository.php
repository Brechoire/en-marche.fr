<?php

namespace AppBundle\Repository;

use AppBundle\Entity\CommitteeFeedItem;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class CommitteeFeedItemRepository extends EntityRepository
{
    public function findPaginatedMostRecentFeedItems(string $committeeUuid, int $limit, int $firstResultIndex = 0): Paginator
    {
        $qb = $this
            ->createCommitteeTimelineQueryBuilder($committeeUuid)
            ->setFirstResult($firstResultIndex)
            ->setMaxResults($limit);

        return new Paginator($qb);
    }

    public function findMostRecentFeedMessage(string $committeeUuid = null, ?bool $published = null): ?CommitteeFeedItem
    {
        return $this->findMostRecentFeedItem(CommitteeFeedItem::MESSAGE, $committeeUuid, $published);
    }

    public function findMostRecentFeedEvent(string $committeeUuid = null, ?bool $published = null): ?CommitteeFeedItem
    {
        return $this->findMostRecentFeedItem(CommitteeFeedItem::EVENT, $committeeUuid, $published);
    }

    private function findMostRecentFeedItem(string $type, string $committeeUuid = null, ?bool $published = null): ?CommitteeFeedItem
    {
        $qb = $this
            ->createQueryBuilder('i')
            ->select('i, a, e')
            ->leftJoin('i.author', 'a')
            ->leftJoin('i.event', 'e')
            ->where('i.itemType = :type')
            ->setMaxResults(1)
            ->setParameter('type', $type)
            ->orderBy('i.createdAt', 'DESC');

        if (null !== $published) {
            $qb->andWhere('i.published = :published')->setParameter('published', $published);
        }

        if ($committeeUuid) {
            $qb
                ->leftJoin('i.committee', 'c')
                ->andWhere('c.uuid = :committee')
                ->setParameter('committee', $committeeUuid);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function createCommitteeTimelineQueryBuilder(string $committeeUuid): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i');

        $qb
            ->select('i, a, e')
            ->leftJoin('i.author', 'a')
            ->leftJoin('i.event', 'e')
            ->leftJoin('i.committee', 'c')
            ->where('c.uuid = :committee')
            ->andWhere('i.published = :published')
            ->orderBy('i.id', 'DESC')
            ->setParameter('committee', $committeeUuid)
            ->setParameter('published', true);

        return $qb;
    }
}
