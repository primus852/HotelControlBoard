<?php

namespace App\Repository;

use App\Entity\Ratecode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Ratecode|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ratecode|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ratecode[]    findAll()
 * @method Ratecode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RatecodeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Ratecode::class);
    }

    // /**
    //  * @return Ratecode[] Returns an array of Ratecode objects
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
    public function findOneBySomeField($value): ?Ratecode
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
