<?php

namespace App\Repository;

use App\Entity\SupportDepartment;
use App\Entity\SupportMessage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportMessage>
 */
class SupportMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportMessage::class);
    }

    /** @return SupportMessage[] */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.createdBy = :u')
            ->setParameter('u', $user)
            ->orderBy('m.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return SupportMessage[] */
    public function findDepartmentInbox(SupportDepartment $dept): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.type = :t')
            ->andWhere('m.department = :d')
            ->setParameter('t', SupportMessage::TYPE_CUSTOMER)
            ->setParameter('d', $dept)
            ->orderBy('m.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return SupportMessage[] */
    public function findInternalThreadsForDepartment(SupportDepartment $dept): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.type = :t')
            ->andWhere('m.department = :d OR m.fromDepartment = :d')
            ->setParameter('t', SupportMessage::TYPE_INTERNAL)
            ->setParameter('d', $dept)
            ->orderBy('m.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function createAdminSearchQuery(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('m')
            ->distinct()
            ->leftJoin('m.department', 'd')->addSelect('d')
            ->leftJoin('m.fromDepartment', 'fd')->addSelect('fd')
            ->leftJoin('m.assignedTo', 'a')->addSelect('a')
            ->leftJoin('m.tags', 't')->addSelect('t')
            ->orderBy('m.updatedAt', 'DESC');

        if (!empty($filters['q'])) {
            $raw = trim((string) $filters['q']);
            $q = '%'.(\function_exists('mb_strtolower') ? mb_strtolower($raw) : strtolower($raw)).'%';
            $qb->andWhere('LOWER(m.subject) LIKE :q OR LOWER(m.message) LIKE :q OR LOWER(m.customerEmail) LIKE :q')
                ->setParameter('q', $q);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('m.status = :status')->setParameter('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $qb->andWhere('m.priority = :priority')->setParameter('priority', $filters['priority']);
        }

        if (!empty($filters['type'])) {
            $qb->andWhere('m.type = :type')->setParameter('type', $filters['type']);
        }

        if (!empty($filters['department'])) {
            $qb->andWhere('d.id = :dept')->setParameter('dept', (int) $filters['department']);
        }

        if (!empty($filters['assigned'])) {
            if ($filters['assigned'] === 'unassigned') {
                $qb->andWhere('m.assignedTo IS NULL');
            } elseif ($filters['assigned'] === 'assigned') {
                $qb->andWhere('m.assignedTo IS NOT NULL');
            }
        }

        if (!empty($filters['tag'])) {
            $qb->andWhere('t.id = :tag')->setParameter('tag', (int) $filters['tag']);
        }

        return $qb;
    }
}
