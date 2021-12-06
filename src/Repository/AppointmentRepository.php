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
            ->andWhere('a.start <= :start AND a.end > :start')
            ->orWhere('a.start < :end AND a.end >= :end')
            /*->orWhere('a.end >= :end AND a.start < :end')*/
            /*->orWhere('a.end > :end AND a.start <= :start')*/
            ->orWhere('a.start <= :start AND :end <= a.end')
            ->orWhere(':start <= a.start AND :end >= a.end')
           /* ->orWhere('a.start > :start AND a.end <= :end')*/
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->andWhere('a.isDeleted = 0')
            ->getQuery()
            ->getResult();

        /*return $qb->getQuery()->getResult();*/

    }

    public function getAppointmentsWhereClientsExist()
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.client IS NOT NULL')
            ->andWhere('a.isDeleted = 0');

        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }

    public function getAppointmentsOfLoggedUser($loggedUserId)
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->join('a.client', 'c')
            ->where('a.client IS NOT NULL')
            ->andWhere("u.id =$loggedUserId" )
            /*->andWhere('c.isDeleted = 0' )*/
            ->andWhere('a.isDeleted = 0');

        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }

    public function getAppointmentsOfLoggedUserEvenDeleted($loggedUserId)
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->join('a.client', 'c')
            ->where('a.client IS NOT NULL')
            ->andWhere("u.id =$loggedUserId" );

        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }


    public function getDoneAppointments()
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.client IS NOT NULL')
            ->andWhere("a.isDone != 0" )
            ->andWhere("a.isDone != 1" )
            ->andWhere('a.isDeleted = 0');

        return $qb->getQuery()->getResult();
    }

    public function getUpcomingAppointments()
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.client IS NOT NULL')
            ->andWhere("a.isDone = 0")
            ->orWhere("a.isDone = 1")
            ->andWhere('a.isDeleted = 0');

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
        $query->setParameters($filters)
            ->andWhere('a.isDeleted = 0');

        /*dd($query->getQuery());*/
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

    public function getAllAppointmentsOfUser($Id)
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->where("u.id = $Id")
            ->andWhere('a.isDeleted = 0');

        return $qb->getQuery()->getResult();
    }

    public function fetchAppointmentsbyFiltersForLoggedCommercial(array $filters, $loggedUserId): array
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
        $query->andWhere('a.client IS NOT NULL')
            ->andWhere('a.isDeleted = 0')
            ->andWhere("u.id = $loggedUserId" );
        $query->setParameters($filters);
        return $query->getQuery()->getResult();
    }

    public function getDeletedAppointments()
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.isDeleted = 1')
            ->orderBy('a.deletionDate', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function getAppointmentsWhereClientsExistCommercialStats()
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.eventType', 'e')
            ->where('e.id = 4');
        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }

    public function getDoneAppointmentsByUser($Id)
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->where('a.client IS NOT NULL')
            ->andWhere("u.id =$Id" )
            ->andWhere('a.isDone = 2')
            ->orWhere('a.isDone = 3')
            ->andWhere('a.isDeleted = 0');

        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }

    public function getFixedAppointmentsByUser($Id)
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.appointmentFixer', 'u')
            ->where('a.client IS NOT NULL')
            ->andWhere("u.id =$Id" )
            ->andWhere('a.isDeleted = 0');

        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }
    public function getUpcomingAppointmentsByUser($Id)
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->where('a.client IS NOT NULL')
            ->andWhere("u.id =$Id" )
            ->andWhere('a.isDone = 0')
            ->orWhere('a.isDone = 1')
            ->andWhere('a.isDeleted = 0');

        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }

    public function getMyAssignedAppointmentsByUser($Id)
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->where('a.client IS NOT NULL')
            ->andWhere("u.id =$Id" );

        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }

    public function getDeletedAppointmentsByUser($Id)
    {

        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->where('a.client IS NOT NULL')
            ->andWhere("u.id =$Id" )
            ->andWhere('a.isDeleted = 1');

        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }

    public function getNotDeletedAppointmentsByClient($id)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.client', 'c')
            ->where('a.isDeleted = 0')
            ->andWhere("c.id = $id");
        return $qb->getQuery()->getResult();
    }

    public function getPostponedAppointments()
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.isDeleted = 0')
            ->andWhere("a.isDone = 1")
            ->andWhere('a.client IS NOT NULL');
        return $qb->getQuery()->getResult();
    }

    public function getArguAppointments()
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.isDeleted = 0')
            ->andWhere("a.isDone = 2")
            ->andWhere('a.client IS NOT NULL');
        return $qb->getQuery()->getResult();
    }

    public function getVenteAppointments()
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->where('a.isDeleted = 0')
            ->andWhere("a.isDone = 3")
            ->andWhere('a.client IS NOT NULL');
        return $qb->getQuery()->getResult();
    }

    public function getPostponedAppointmentsByUser($Id)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->where('a.client IS NOT NULL')
            ->andWhere("u.id =$Id" )
            ->andWhere('a.isDone = 1')
            ->andWhere('a.isDeleted = 0');
        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }

    public function getArguAppointmentsByUser($Id)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->where('a.client IS NOT NULL')
            ->andWhere("u.id =$Id" )
            ->andWhere('a.isDone = 2')
            ->andWhere('a.isDeleted = 0');
        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }

    public function getVenteAppointmentsByUser($Id)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('a')
            ->join('a.user', 'u')
            ->where('a.client IS NOT NULL')
            ->andWhere("u.id =$Id" )
            ->andWhere('a.isDone = 3')
            ->andWhere('a.isDeleted = 0');
        return $qb->orderBy('a.start', 'DESC')->getQuery()->getResult();
    }

}
