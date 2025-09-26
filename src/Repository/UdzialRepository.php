<?php

namespace App\Repository;

use App\Entity\Udzial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Udzial>
 */
class UdzialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Udzial::class);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findByApartment(int $apartmentId): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.apartment = :apartmentId')
            ->setParameter('apartmentId', $apartmentId)
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndApartment(int $userId, int $apartmentId): ?Udzial
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.user = :userId')
            ->andWhere('u.apartment = :apartmentId')
            ->setParameter('userId', $userId)
            ->setParameter('apartmentId', $apartmentId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getTotalPercentageForApartment(int $apartmentId): float
    {
        $result = $this->createQueryBuilder('u')
            ->select('SUM(u.procent) as total')
            ->andWhere('u.apartment = :apartmentId')
            ->setParameter('apartmentId', $apartmentId)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
