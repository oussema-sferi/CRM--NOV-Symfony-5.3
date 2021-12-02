<?php

namespace App\Repository;

use App\Entity\ClientCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClientCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClientCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientCategory[]    findAll()
 * @method ClientCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClientCategory::class);
    }

    // /**
    //  * @return ClientCategory[] Returns an array of ClientCategory objects
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
    public function findOneBySomeField($value): ?ClientCategory
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function getSortedByDateCategories()
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c');
        return $qb->orderBy('c.createdAt', 'DESC')->getQuery()->getResult();
    }
}
