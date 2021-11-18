<?php

namespace App\Repository;

use App\Entity\Diagnostic;
use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /**
     * @return Question Returns an array of Question objects
     * @throws NonUniqueResultException
     */
    public function getLastUpdate(Diagnostic $diagnostic): ?Question
    {
        return $this->createQueryBuilder('q')
            ->where('q.diagnostic = :diag')
            ->setParameter('diag', $diagnostic)
            ->orderBy('q.lastUpdate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Question Returns an array of Question objects
     * @throws NonUniqueResultException
     */
    public function lastInCat(Diagnostic $diagnostic, Question $question): ?Question
    {
        return $this->createQueryBuilder('q')
            ->andwhere('q.id != :id')
            ->setParameter('id', $question->getId())
            ->andwhere('q.diagnostic = :diag')
            ->setParameter('diag', $diagnostic)
            ->andwhere('q.category = :cat')
            ->setParameter('cat', $question->getCategory())
            ->orderBy('q.rang', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
