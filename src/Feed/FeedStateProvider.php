<?php

namespace App\Feed;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\CustomList;
use App\Entity\Rating;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class FeedStateProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): FeedResult
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new FeedResult();
        }

        $cursor = $context['request']->query->get('cursor', null);
        $limit = (int) ($context['request']->query->get('limit') ?? 20);

        $cursorDate =  new \DateTimeImmutable($cursor);

        $followedUsers = $user->getFollows()->toArray();
        $followedUsers[] = $user; // include user's activity in follows


        $activities = [];

        $sources = [
            ['entity' => Rating::class, 'field' => 'createdAt', 'type' => 'RATING'],
            ['entity' => Review::class, 'field' => 'createdAt', 'type' => 'REVIEW'],
            ['entity' => CustomList::class, 'field' => 'createdAt', 'type' => 'COLLECTION'],
        ];

        foreach ($sources as $source) {
            $qb = $this->em->getRepository($source['entity'])->createQueryBuilder('e');

            $qb->where('e.user IN (:users)')
                ->andWhere(sprintf('e.%s < :cursor', $source['field']))
                ->setParameter('users', $followedUsers)
                ->setParameter('cursor', $cursorDate);

            $items = $qb->orderBy('e.'.$source['field'], 'DESC')
                ->setMaxResults($limit + 1) // one extra to compute next cursor
                ->getQuery()
                ->getResult();

            foreach ($items as $entity) {
                $f = new FeedItem();
                $f->type = $source['type'];
                $f->data = $entity;
                $f->createdAt = $entity->getCreatedAt();
                $activities[] = $f;
            }
        }

        usort($activities, fn($a, $b) => $b->createdAt <=> $a->createdAt);

        // Keep only LIMIT
        $slice = array_slice($activities, 0, $limit);

        // Compute next cursor (if more available)
        $nextCursor = null;
        if (count($activities) > $limit) {
            $nextCursor = end($slice)->createdAt->format('c');
        }

        $result = new FeedResult();
        $result->items = $slice;
        $result->nextCursor = $nextCursor;

        return $result;
    }
}
