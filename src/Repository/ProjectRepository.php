<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public const USER = 'user';
    public const EQUIPMENT = 'equipment';
    public const STATUS = 'status';
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    // /**
    //  * @return Project[] Returns an array of Project objects
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
    public function findOneBySomeField($value): ?Project
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function getProjectsOfLoggedUser($loggedUserId)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p')
            ->join('p.user', 'u')
            ->where("u.id =$loggedUserId" );
        return $qb->orderBy('a.createdAt', 'DESC')->getQuery()->getResult();
    }

    /**
     * @param array<mixed> $filters
     * @return array<string>
     **/
    private function _trimFiltersApp(array $filters): array
    {
        /** @var array<string> $result **/
        $result = [];
        foreach ($filters as $key => $value) {
            if (trim($value) !== "") {
                if (($key !== self::USER) && ($key !== self::EQUIPMENT) && ($key !== self::STATUS)) {
                    $result[$key] = '%'.$value.'%';
                }
                else {
                    $result[$key] = $value;
                }

            }
        }
        return $result;
    }


    public function fetchAllProjectsByFilters(array $filters): array
    {
        $builder = $this->createQueryBuilder('p');
        $query = $builder->select('p')
            ->join('p.projectMakerUser', 'u')
            ->join('p.client', 'c')
            ->join('p.equipment', 'e');
        $counter = 0;
        $filters = $this->_trimFiltersApp($filters);
        /*dd($filters);*/
        foreach ($this->_trimFiltersApp($filters) as $key => $value) {
            if($key === self::USER) {
                $statement = " u.id = :$key";
            } elseif ($key === self::STATUS) {
                $statement = "p.status = :$key";
            } elseif ($key === self::EQUIPMENT) {
                $statement = "e.id = :$key";
            } else {
                $statement = "c.$key LIKE :$key";
            }

            if ($counter === 0) {
                $query->where($statement);
            }
            else {
                $query->andWhere($statement);
            }
            $counter ++;
        }
        $query->setParameters($filters);
        return $query->getQuery()->getResult();
    }
}
