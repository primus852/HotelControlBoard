<?php

namespace App\Repository;

use App\Entity\JournalBudget;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method JournalBudget|null find($id, $lockMode = null, $lockVersion = null)
 * @method JournalBudget|null findOneBy(array $criteria, array $orderBy = null)
 * @method JournalBudget[]    findAll()
 * @method JournalBudget[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JournalBudgetRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, JournalBudget::class);
    }

    // /**
    //  * @return JournalBudget[] Returns an array of JournalBudget objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('j.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?JournalBudget
    {
        return $this->createQueryBuilder('j')
            ->andWhere('j.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
