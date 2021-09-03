<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Client|null find($id, $lockMode = null, $lockVersion = null)
 * @method Client|null findOneBy(array $criteria, array $orderBy = null)
 * @method Client[]    findAll()
 * @method Client[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClientRepository extends ServiceEntityRepository
{
    public const GEOGRAPHIC_AREA = 'geographicArea';
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    // /**
    //  * @return Client[] Returns an array of Client objects
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
    public function findOneBySomeField($value): ?Client
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findClientsByFilterAndKeyword($filter, $keyword)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where("c.$filter LIKE :keyword")
            ->setParameter('keyword', '%'.$keyword.'%');

        return $qb->getQuery()->getResult();
    }

    public function fetchClientsbyFilters(array $filters): array
    {
        $builder = $this->createQueryBuilder('c');
        $query = $builder->select('c')
            ->join('c.geographicArea', 'g');
        $counter = 0;
        $filters = $this->_trimFilters($filters);
        foreach ($this->_trimFilters($filters) as $key => $value) {
            $statement = $key === self::GEOGRAPHIC_AREA ? " g.id = :$key" : "c.$key LIKE :$key";
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

    /**
     * @param array<mixed> $filters
     * @return array<string>
    **/
    private function _trimFilters(array $filters): array
    {
        /** @var array<string> $result **/
        $result = [];
        foreach ($filters as $key => $value) {
            if (trim($value) !== "") {
                if ($key !== self::GEOGRAPHIC_AREA) {
                    $result[$key] = '%'.$value.'%';
                }
                else {
                    $result[$key] = $value;
                }

            }
    }
        return $result;
    }


    /*public function findClientsByKeyword($keyword)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where("c.$filter LIKE :keyword")
            ->setParameter('keyword', '%'.$keyword.'%');

        return $qb->getQuery()->getResult();
    }*/

    /*public function findFreeClients()
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->join('c.appointment', 'a')
            ->where("a.id != ")
            ->setParameter('keyword', '%'.$keyword.'%');

        return $qb->getQuery()->getResult();
    }*/

    public function getNotFixedAppointmentsClients()
    {

        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.statusDetail IS NOT NULL AND c.statusDetail != 7')
            ->orWhere('c.statusDetail IS NULL');
        return $qb->getQuery()->getResult();
    }

    public function getProcessedClients()
    {

        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.status != 0');

        return $qb->getQuery()->getResult();
    }

    public function findClientsByTeleproDepartments($departmentsArrayIds)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->join('c.geographicArea', 'g');
        $counter = 0;
        if(count($departmentsArrayIds) === 0) {
            return [];
        }
        foreach ($departmentsArrayIds as $departmentId) {
            $statement = "g.id = $departmentId";
            if ($counter === 0) {
                $qb->where($statement);
            } else {
                $qb->orWhere($statement);
            }
            $counter ++;
        };
        $qb->andWhere('c.statusDetail != 7');
        return $qb->getQuery()->getResult();
    }

    public function fetchAssignedClientsbyFilters(array $departmentsArrayIds, array $filters): array
    {
        $builder = $this->createQueryBuilder('c');
        $query = $builder->select('c')
            ->join('c.geographicArea', 'g');
        $counter = 0;
        $filters = $this->_trimFilters($filters);
        foreach ($this->_trimFilters($filters) as $key => $value) {

            foreach ($departmentsArrayIds as $departmentId) {
                $statement1 = "g.id = $departmentId";
                if ($counter === 0) {
                    $query->where($statement1);
                } else {
                    $query->orWhere($statement1);
                }
                $counter ++;
            };
            /*dd($query->getQuery());*/
            $statement2 = $key === self::GEOGRAPHIC_AREA ? " g.id = :$key" : "c.$key LIKE :$key";
                $query->andWhere($statement2);

        }
        /*dd($query->getQuery());*/
        $query->andWhere('c.statusDetail != 7');
        $query->setParameters($filters);
        return $query->getQuery()->getResult();
    }



}
