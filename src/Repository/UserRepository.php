<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function findUsersByCommercialRole($role)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->where('u.roles LIKE :roles')
            ->andWhere('u.isDeleted = 0')
            ->setParameter('roles', '%"'.$role.'"%');

        return $qb->getQuery()->getResult();
    }

    public function findAssignedUsersByCommercialRole($id, $role)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->join('u.teleprospector', 't')
            ->where("t.id = $id")
            ->andWhere('u.roles LIKE :roles')
            ->andWhere('u.isDeleted = 0')
            ->setParameter('roles', '%"'.$role.'"%');
        return $qb->getQuery()->getResult();
    }

    public function findFreeCommercials($busyCommercialsIdsArray, $role, $teleprospectorId)
    {
        $qb = $this->createQueryBuilder('u');
        $query = $qb->select('u')
            ->join('u.teleprospector', 't')
            ->where("t.id = $teleprospectorId");
        foreach ($busyCommercialsIdsArray as $id) {
            $query->andWhere("u.id != $id");
        }
        $query->andWhere('u.roles LIKE :roles')
            ->andWhere('u.isDeleted = 0')
            ->setParameter('roles', '%"'.$role.'"%');
        return $query->getQuery()->getResult();
    }

    public function findFreeCommercialsForSuperAdmin($busyCommercialsIdsArray, $role)
    {
        $qb = $this->createQueryBuilder('u');
        $query = $qb->select('u')
            ->where('u.roles LIKE :roles')
            ->andWhere('u.isDeleted = 0');
        foreach ($busyCommercialsIdsArray as $id) {
            $query->andWhere("u.id != $id");
        }
        $query->setParameter('roles', '%"'.$role.'"%');
        return $query->getQuery()->getResult();
    }

    public function getNotDeletedUsers()
    {

        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->where('u.isDeleted = 0');

        return $qb->getQuery()->getResult();
    }

    public function getDeletedUsers()
    {

        $qb = $this->createQueryBuilder('u');
        $qb->select('u')
            ->where('u.isDeleted = 1');

        return $qb->getQuery()->getResult();
    }
}
