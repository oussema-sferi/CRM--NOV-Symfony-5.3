<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Appointment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Appointment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Appointment[]    findAll()
 * @method Appointment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public const USER = 'user';
    public const GEOGRAPHICAREA = 'geographicArea';
    public const ISDONE = 'isDone';
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    // /**
    //  * @return Appointment[] Returns an array of Appointment objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Appointment
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function getAppointmentsBetweenByDate($start, $end)
    {

        /*return $this->createQueryBuilder("a")
            ->andWhere('a.start < :start AND a.end <= :start')
            ->orWhere('a.start >= :end AND a.end > :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();*/
        return $this->createQueryBuilder("a")
            ->andWhere('a.start <= :start AND a.end >= :start')
            ->andWhere('a.start <= :end AND a.end >= :end')
            ->orWhere('a.end >= :end AND a.start < :end')
            ->orWhere('a.end > :start AND a.start <= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        /*return $qb->getQuery()->getResult();*/

    }

    public function getAppointmentsWhereClientsExist()
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.client IS NOT NULL');

        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }

    public function getAppointmentsOfLoggedUser($loggedUserId)
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->where('a.client IS NOT NULL')
            ->andWhere("u.id =$loggedUserId" );

        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }

    public function getDoneAppointments()
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.client IS NOT NULL')
            ->andWhere("a.isDone = 1" );

        return $qb->getQuery()->getResult();
    }

    public function getUpcomingAppointments()
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.client IS NOT NULL')
            ->andWhere("a.isDone IS NULL" );

        return $qb->getQuery()->getResult();
    }

    public function fetchAppointmentsbyFilters(array $filters): array
    {
        $builder = $this->createQueryBuilder('a');
        $query = $builder->select('a')
            ->join('a.user', 'u')
            ->join('a.client', 'c')
            ->join('c.geographicArea', 'g');
        $counter = 0;
        $filters = $this->_trimFiltersApp($filters);
        /*dd($filters);*/
        foreach ($this->_trimFiltersApp($filters) as $key => $value) {
            if($key === self::USER) {
                $statement = " u.id = :$key";
            } elseif ($key === self::ISDONE) {
                $statement = "a.$key = :$key";
            } elseif ($key === 'start') {
                $statement = "a.$key >= :$key";
                /*dd("test");*/
            } elseif ($key === 'end') {
                $statement = "a.$key <= :$key";
                /*dd("test");*/
            } elseif ($key === self::GEOGRAPHICAREA) {
                $statement = "g.id = :$key";
                /*dd("test");*/
            }
            else {
                $statement = "c.$key LIKE :$key";
            }
            /*$statement = $key === self::USER ? " u.id = :$key" : "c.$key LIKE :$key";*/
            /*dd($key);*/
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
    private function _trimFiltersApp(array $filters): array
    {
        /** @var array<string> $result **/
        $result = [];
        foreach ($filters as $key => $value) {
            if (trim($value) !== "") {
                if (($key !== self::USER) && ($key !== self::GEOGRAPHICAREA) && ($key !== self::ISDONE)
                    && ($key !== 'start') && ($key !== 'end')) {
                    $result[$key] = '%'.$value.'%';
                    /*dd($result[$key]);*/
                }
                else {
                    $result[$key] = $value;

                }

            }
        }
        return $result;
    }

    public function getAppointmentsOfUser($Id)
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->where("u.id = $Id");

        return $qb->getQuery()->getResult();
    }


}
