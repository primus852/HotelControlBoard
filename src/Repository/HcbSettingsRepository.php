<?php

namespace App\Repository;

use App\Entity\HcbSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method HcbSettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method HcbSettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method HcbSettings[]    findAll()
 * @method HcbSettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HcbSettingsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, HcbSettings::class);
    }

    // /**
    //  * @return HcbSettings[] Returns an array of HcbSettings objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?HcbSettings
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
