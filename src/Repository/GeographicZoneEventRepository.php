<?php

namespace App\Repository;

use App\Entity\GeographicZoneEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GeographicZoneEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method GeographicZoneEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method GeographicZoneEvent[]    findAll()
 * @method GeographicZoneEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeographicZoneEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeographicZoneEvent::class);
    }

    // /**
    //  * @return GeographicZoneEvent[] Returns an array of GeographicZoneEvent objects
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
    public function findOneBySomeField($value): ?GeographicZoneEvent
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getUserGeographicZoneEvents($id)
    {
        $qb = $this->createQueryBuilder('g');
        $qb->select('g')
            ->join('g.calendarUser', 'u')
            ->where("u.id=$id");

        return $qb->getQuery()->getResult();
    }
}
