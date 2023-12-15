<?php

namespace App\Tree;

use Doctrine\ORM\EntityManagerInterface as EntityManager;
use App\Entity\TreeScene;
use App\Utility\CodeGenerator;

use DateTimeImmutable;
use DateInterval;

class Manager
{
    public const VALID_DAYS = 2;

    private $treeSceneRepository;

    public function __construct(private EntityManager $entityManager, private CodeGenerator $codeGenerator)
    {
        $this->treeSceneRepository = $entityManager->getRepository(TreeScene::class);
    }

    public function createTree(): ?TreeScene
    {
        $code = $this->codeGenerator->generateCode();
        $tree = new TreeScene($code);
        $tree->setData($this->getDefaultState());
        $tree->setPassword(null);
        $tree->setRevision(0);
        try {
            $this->store($tree);
        } catch (Exception $e) {
            return null;
        }
        return $tree;
    }

    public function store(TreeScene $tree): void
    {
        $tree->setRevision($tree->getRevision() + 1);
        $tree->setValidTill((new DateTimeImmutable())->add(new DateInterval("P" . self::VALID_DAYS . "D")));
        $this->entityManager->persist($tree);
        $this->entityManager->flush();
    }

    public function refresh(TreeScene $tree): void
    {
        $this->entityManager->refresh($tree);
    }

    private function getDefaultState(): array
    {
        return [];
    }
}
