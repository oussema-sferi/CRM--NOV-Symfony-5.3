<?php

namespace App\Repository;

use App\Entity\PaymentSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PaymentSchedule|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentSchedule|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentSchedule[]    findAll()
 * @method PaymentSchedule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentSchedule::class);
    }

    // /**
    //  * @return PaymentSchedule[] Returns an array of PaymentSchedule objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PaymentSchedule
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
