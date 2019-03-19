<?php

namespace App\Repository;

use App\Entity\Rateplan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Rateplan|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rateplan|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rateplan[]    findAll()
 * @method Rateplan[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RateplanRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Rateplan::class);
    }

    // /**
    //  * @return Rateplan[] Returns an array of Rateplan objects
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
    public function findOneBySomeField($value): ?Rateplan
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
