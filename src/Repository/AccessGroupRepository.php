<?php

namespace App\Repository;

use App\Entity\AccessGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AccessGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method AccessGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method AccessGroup[]    findAll()
 * @method AccessGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccessGroupRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AccessGroup::class);
    }

    // /**
    //  * @return AccessGroup[] Returns an array of AccessGroup objects
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
    public function findOneBySomeField($value): ?AccessGroup
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
