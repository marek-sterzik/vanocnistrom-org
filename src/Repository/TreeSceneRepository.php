<?php

namespace App\Repository;

use App\Entity\TreeScene;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use DateTimeImmutable;

/**
 * @extends ServiceEntityRepository<TreeScene>
 *
 * @method TreeScene|null find($id, $lockMode = null, $lockVersion = null)
 * @method TreeScene|null findOneBy(array $criteria, array $orderBy = null)
 * @method TreeScene[]    findAll()
 * @method TreeScene[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TreeSceneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TreeScene::class);
    }

    public function cleanup(): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.validTill < :now')
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getResult()
        ;
    }
}
