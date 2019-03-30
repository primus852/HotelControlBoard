<?php

namespace App\Repository;

use App\Entity\CompetitorCheck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CompetitorCheck|null find($id, $lockMode = null, $lockVersion = null)
 * @method CompetitorCheck|null findOneBy(array $criteria, array $orderBy = null)
 * @method CompetitorCheck[]    findAll()
 * @method CompetitorCheck[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CompetitorCheckRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CompetitorCheck::class);
    }

    // /**
    //  * @return CompetitorCheck[] Returns an array of CompetitorCheck objects
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
    public function findOneBySomeField($value): ?CompetitorCheck
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
