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
    public const EQUIPMENT = 'equipment';
    public const FIRSTNAME = 'CLIENT';
    public const LASTNAME = 'CLIENT';
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
                if (($key !== self::EQUIPMENT) && ($key !== "numberOfMonthlyPayments") && ($key !== "totalHT") && ($key !== "isCompleted")) {
                    $result[$key] = '%'.$value.'%';
                }
                else {
                    $result[$key] = $value;
                }

            }
        }
        return $result;
    }


    public function fetchAllPaymentsSchedulesByFilters(array $filters): array
    {
        $builder = $this->createQueryBuilder('p');
        $query = $builder->select('p')
            ->join('p.client', 'c')
            ->join('p.project', 'proj')
            ->join('proj.equipment', 'e');
        $counter = 0;
        $filters = $this->_trimFiltersApp($filters);
        /*dd($filters);*/
        foreach ($this->_trimFiltersApp($filters) as $key => $value) {
            if($key === self::EQUIPMENT) {
                $statement = "e.id = :$key";
            } elseif (($key === "firstName") || ($key === "lastName")) {
                $statement = "c.$key LIKE :$key";
            } elseif (($key === "numberOfMonthlyPayments") || ($key === "totalHT")) {
                $statement = "proj.$key = :$key";
            }else {
                $statement = "p.$key = :$key";
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

    public function getPaymentSchedulesForFollowUpClients()
    {
        $qb = $this->createQueryBuilder('p');
        $qb->select('p')
            ->where('p.isDeleted = 0');
            /*->andWhere('c.isDeleted != 0');*/

        return $qb->getQuery()->getResult();
    }
}
