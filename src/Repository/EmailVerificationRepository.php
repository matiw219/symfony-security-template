<?php

namespace App\Repository;

use App\Entity\EmailVerification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailVerification>
 *
 * @method EmailVerification|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailVerification|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailVerification[]    findAll()
 * @method EmailVerification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailVerificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailVerification::class);
    }
}
