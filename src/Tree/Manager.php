<?php

namespace App\Tree;

use Doctrine\ORM\EntityManagerInterface as EntityManager;
use App\Entity\TreeScene;
use App\Repository\TreeSceneRepository;
use App\Utility\CodeGenerator;

use DateTimeImmutable;
use DateInterval;
use Exception;

class Manager
{
    public const VALID_DAYS = 2;

    public function __construct(
        private EntityManager $entityManager,
        private TreeSceneRepository $treeSceneRepository,
        private CodeGenerator $codeGenerator
    ) {
    }

    public function cleanup(): void
    {
        $this->treeSceneRepository->cleanup();
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

    public function getTerminalCode(TreeScene $tree, int $cols, int $rows): string
    {
        $christmasTree = new ChristmasTree();
        $christmasTree->putStar(4);
        $buffer = new Output\BufferOutput($cols, $rows);
        $christmasTree->clearOutput($buffer);
        $christmasTree->render($buffer);
        return $buffer->getContent(true);
    }

    private function getDefaultState(): array
    {
        return [];
    }
}
