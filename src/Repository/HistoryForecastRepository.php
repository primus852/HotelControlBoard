<?php

namespace App\Repository;

use App\Entity\HistoryForecast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method HistoryForecast|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoryForecast|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistoryForecast[]    findAll()
 * @method HistoryForecast[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HistoryForecastRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, HistoryForecast::class);
    }

    // /**
    //  * @return HistoryForecast[] Returns an array of HistoryForecast objects
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
    public function findOneBySomeField($value): ?HistoryForecast
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
