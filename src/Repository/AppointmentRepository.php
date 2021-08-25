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
}
