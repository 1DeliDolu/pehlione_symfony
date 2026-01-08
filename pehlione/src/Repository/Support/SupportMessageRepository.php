<?php

namespace App\Repository\Support;

use App\Entity\Support\SupportMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class SupportMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportMessage::class);
    }

    public function countUnread(): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.status = :s')
            ->setParameter('s', SupportMessage::STATUS_NEW)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnreadForDepartments(array $allowedDepartmentIds): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.department', 'd')
            ->andWhere('m.status = :s')
            ->andWhere('d.id IN (:allowed)')
            ->setParameter('s', SupportMessage::STATUS_NEW)
            ->setParameter('allowed', $allowedDepartmentIds ?: [0])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Basit listeleme: status + q (subject/fromEmail) + departman
     */
    public function findForInbox(?string $status = null, ?string $q = null, ?int $departmentId = null, ?array $allowedDepartmentIds = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.department', 'd')->addSelect('d')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit);

        // Yetkili departmanlarla sınırla
        if ($allowedDepartmentIds !== null) {
            $qb->andWhere('d.id IN (:allowed)')->setParameter('allowed', $allowedDepartmentIds ?: [0]);
        }

        if ($departmentId) {
            $qb->andWhere('d.id = :did')->setParameter('did', $departmentId);
        }

        if ($status) {
            $qb->andWhere('m.status = :st')->setParameter('st', $status);
        }

        if ($q) {
            $qb->andWhere('m.subject LIKE :q OR m.fromEmail LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        return $qb->getQuery()->getResult();
    }
}
