<?php

namespace App\Repository;

use App\Entity\Ratetype;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Ratetype|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ratetype|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ratetype[]    findAll()
 * @method Ratetype[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RatetypeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Ratetype::class);
    }

    // /**
    //  * @return Ratetype[] Returns an array of Ratetype objects
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
    public function findOneBySomeField($value): ?Ratetype
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
