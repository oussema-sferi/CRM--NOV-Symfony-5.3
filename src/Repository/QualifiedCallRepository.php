<?php

namespace App\Repository;

use App\Entity\QualifiedCall;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method QualifiedCall|null find($id, $lockMode = null, $lockVersion = null)
 * @method QualifiedCall|null findOneBy(array $criteria, array $orderBy = null)
 * @method QualifiedCall[]    findAll()
 * @method QualifiedCall[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QualifiedCallRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QualifiedCall::class);
    }

    // /**
    //  * @return QualifiedCall[] Returns an array of QualifiedCall objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('q.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?QualifiedCall
    {
        return $this->createQueryBuilder('q')
            ->andWhere('q.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
