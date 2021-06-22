<?php

namespace App\Repository;

use App\Entity\GeographicArea;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GeographicArea|null find($id, $lockMode = null, $lockVersion = null)
 * @method GeographicArea|null findOneBy(array $criteria, array $orderBy = null)
 * @method GeographicArea[]    findAll()
 * @method GeographicArea[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeographicAreaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeographicArea::class);
    }

    // /**
    //  * @return GeographicArea[] Returns an array of GeographicArea objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('g.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?GeographicArea
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
