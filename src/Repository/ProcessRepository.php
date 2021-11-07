<?php

namespace App\Repository;

use App\Entity\Process;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Process|null find($id, $lockMode = null, $lockVersion = null)
 * @method Process|null findOneBy(array $criteria, array $orderBy = null)
 * @method Process[]    findAll()
 * @method Process[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProcessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Process::class);
    }

    // /**
    //  * @return Process[] Returns an array of Process objects
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
    public function findOneBySomeField($value): ?Process
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function getProcessesByUser($Id)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p')
            ->join('p.processorUser', 'u')
            ->where("u.id =$Id" );
        return $qb->getQuery()->getResult();
    }

    public function getAllQualifiedProcesses()
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p')
            ->where('p.status = 2');
        return $qb->getQuery()->getResult();
    }

    public function getAllNotQualifiedProcesses()
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p')
            ->where('p.status = 1');
        return $qb->getQuery()->getResult();
    }

    public function getAllQualifiedProcessesByUser($Id)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p')
            ->join('p.processorUser', 'u')
            ->where("u.id =$Id" )
            ->andWhere('p.status = 2');
        return $qb->getQuery()->getResult();
    }

    public function getAllNotQualifiedProcessesByUser($Id)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p')
            ->join('p.processorUser', 'u')
            ->where("u.id =$Id" )
            ->andWhere('p.status = 1');
        return $qb->getQuery()->getResult();
    }
    public function findAllSortedDate()
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p');

        return $qb->orderBy('p.createdAt', 'DESC')->getQuery()->getResult();

    }
}
