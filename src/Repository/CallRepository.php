<?php

namespace App\Repository;

use App\Entity\Call;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Call|null find($id, $lockMode = null, $lockVersion = null)
 * @method Call|null findOneBy(array $criteria, array $orderBy = null)
 * @method Call[]    findAll()
 * @method Call[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CallRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Call::class);
    }

    // /**
    //  * @return Call[] Returns an array of Call objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Call
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getDeletedCalls()
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.isDeleted = 1')
            ->orderBy('c.deletionDate', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function getAllNotDeletedCalls()
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.isDeleted = 0')
            ->orderBy('c.deletionDate', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function getQualifiedCalls()
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.isDeleted = 0')
            ->andWhere('c.generalStatus = 2')
            ->orderBy('c.deletionDate', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function getNotQualifiedCalls()
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.isDeleted = 0')
            ->andWhere('c.generalStatus = 1')
            ->orderBy('c.deletionDate', 'DESC');
        return $qb->getQuery()->getResult();
    }
}
