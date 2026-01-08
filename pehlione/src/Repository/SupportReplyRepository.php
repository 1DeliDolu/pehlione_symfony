<?php

namespace App\Repository;

use App\Entity\SupportReply;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SupportReply>
 *
 * @method SupportReply|null find($id, $lockMode = null, $lockVersion = null)
 * @method SupportReply|null findOneBy(array $criteria, array $orderBy = null)
 * @method SupportReply[]    findAll()
 * @method SupportReply[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupportReplyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportReply::class);
    }

//    /**
//     * @return SupportReply[] Returns an array of SupportReply objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SupportReply
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
