<?php

namespace App\Repository;

use App\Entity\Roomtype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Roomtype|null find($id, $lockMode = null, $lockVersion = null)
 * @method Roomtype|null findOneBy(array $criteria, array $orderBy = null)
 * @method Roomtype[]    findAll()
 * @method Roomtype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomtypeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Roomtype::class);
    }

    // /**
    //  * @return Roomtype[] Returns an array of Roomtype objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Roomtype
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
