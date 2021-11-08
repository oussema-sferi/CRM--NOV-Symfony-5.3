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
        $query->andWhere('c.statusDetail != 7')
            ->andWhere('c.isDeleted = 0');
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
            ->where('c.statusDetail != 7')
            ->andWhere('c.isDeleted = 0')
            ->orderBy('c.createdAt', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function getProcessedClients()
    {

        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.status != 0')
            ->andWhere('c.isDeleted = 0');

        return $qb->getQuery()->getResult();
    }

    public function getNotProcessedClients()
    {

        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.status = 0')
            ->andWhere('c.isDeleted = 0');

        return $qb->getQuery()->getResult();
    }

    public function findClientsByTeleproDepartments($departmentsArrayIds, $loggedUserId)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->join('c.geographicArea', 'g');
            /*->join('c.creatorUser', 'u');*/
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
        $qb->andWhere('c.statusDetail != 7')
            ->andWhere('c.isDeleted = 0');
        /*dd($qb->getQuery()->getResult());*/
        /*$qb->orWhere('u.id = 15');*/
        /*dd($qb->getQuery()->getResult());*/
        $qb2 = $this->createQueryBuilder('c')->select('c')->join('c.creatorUser', 'u');
        $qb2->where("u.id = $loggedUserId");
        /*dd($qb2->getQuery()->getResult());*/
        return array_merge($qb->getQuery()->getResult(),$qb2->getQuery()->getResult());
    }

    public function findAllClientsByUserDepartments($departmentsArrayIds, $loggedUserId)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->join('c.geographicArea', 'g');
        /*->join('c.creatorUser', 'u');*/
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
        $qb->andWhere('c.isDeleted = 0');
        /*dd($qb->getQuery()->getResult());*/
        /*$qb->orWhere('u.id = 15');*/
        /*dd($qb->getQuery()->getResult());*/
        $qb2 = $this->createQueryBuilder('c')->select('c')->join('c.creatorUser', 'u');
        $qb2->where("u.id = $loggedUserId");
        /*dd($qb2->getQuery()->getResult());*/
        return array_merge($qb->getQuery()->getResult(),$qb2->getQuery()->getResult());
    }

    public function fetchAssignedClientsbyFilters(array $departmentsArrayIds, array $filters, int $loggedUserId): array
    {
        $builder = $this->createQueryBuilder('c');
        $query = $builder->select('c')
            ->join('c.geographicArea', 'g');
        $counter = 0;
        $filters = $this->_trimFilters($filters);
        foreach ($this->_trimFilters($filters) as $key => $value) {
            $statement1 = $key === self::GEOGRAPHIC_AREA ? " g.id = :$key" : "c.$key LIKE :$key";
            $query->andWhere($statement1);
            /*dd($query->getQuery());*/
        }
        $statement2 = "";
        for($i = 0; $i < (count($departmentsArrayIds) - 1); $i++) {

            $statement2 = $statement2 . "g.id = $departmentsArrayIds[$i] OR ";

        };
        $statement2 = $statement2 . "g.id = $departmentsArrayIds[$i]";
        $query->andWhere($statement2);
        $query->andWhere('c.statusDetail != 7');
        $query->setParameters($filters);
        /*dd($query->getQuery());*/
        $query2 = $this->createQueryBuilder('c')->select('c')->join('c.geographicArea', 'g')
            ->join('c.creatorUser', 'u');
        foreach ($this->_trimFilters($filters) as $key => $value) {
            $statement3 = $key === self::GEOGRAPHIC_AREA ? " g.id = :$key" : "c.$key LIKE :$key";
            $query2->andWhere($statement3);
        }
        $query2->andWhere('c.statusDetail != 7')
            ->andWhere("u.id = $loggedUserId")
            ->andWhere('c.isDeleted = 0');
        $query2->setParameters($filters);
        /*dd($query2->getQuery()->getResult());*/
        /*dd($query->getQuery());*/
        return array_merge($query->getQuery()->getResult(),$query2->getQuery()->getResult());
    }

    public function fetchClientsbyFiltersAllContacts(array $filters): array
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
        $query->andWhere('c.isDeleted = 0');
        return $query->getQuery()->getResult();
    }

    public function fetchAssignedClientsbyFiltersAllContacts(array $departmentsArrayIds, array $filters, int $loggedUserId): array
    {
        $builder = $this->createQueryBuilder('c');
        $query = $builder->select('c')
            ->join('c.geographicArea', 'g');
        $counter = 0;
        $filters = $this->_trimFilters($filters);
        foreach ($this->_trimFilters($filters) as $key => $value) {
            $statement1 = $key === self::GEOGRAPHIC_AREA ? " g.id = :$key" : "c.$key LIKE :$key";
            $query->andWhere($statement1);
            /*dd($query->getQuery());*/
        }
        $statement2 = "";
        for($i = 0; $i < (count($departmentsArrayIds) - 1); $i++) {

            $statement2 = $statement2 . "g.id = $departmentsArrayIds[$i] OR ";

        };
        $statement2 = $statement2 . "g.id = $departmentsArrayIds[$i]";
        $query->andWhere($statement2);
        $query->setParameters($filters);
        /*dd($query->getQuery());*/
        $query2 = $this->createQueryBuilder('c')->select('c')->join('c.geographicArea', 'g')
            ->join('c.creatorUser', 'u');
        foreach ($this->_trimFilters($filters) as $key => $value) {
            $statement3 = $key === self::GEOGRAPHIC_AREA ? " g.id = :$key" : "c.$key LIKE :$key";
            $query2->andWhere($statement3);
        }
        $query2->andWhere("u.id = $loggedUserId")
                ->andWhere('c.isDeleted = 0');
        $query2->setParameters($filters);
        return array_merge($query->getQuery()->getResult(),$query2->getQuery()->getResult());
    }

    public function getNotDeletedClients()
    {

        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.isDeleted = 0')
            ->orderBy('c.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function getDeletedClients()
    {

        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.isDeleted = 1')
            ->orderBy('c.deletionDate', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /*public function getProcessedClientsByUser($id)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.status != 0')
            ->andWhere('c.isDeleted = 0')
            ->andWhere("c.")

        return $qb->getQuery()->getResult();
    }*/

    public function ajaxClientsSearch($keyword)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c')
            ->where('c.isDeleted = 0')
            ->andWhere("c.lastName LIKE :keyword")
            ->setParameter('keyword', '%'.$keyword.'%');
        return $qb->getQuery()->getResult();
    }

}
