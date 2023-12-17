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
    public const INITIAL_VALID_PERIOD = "PT1H";
    public const VALID_PERIOD = "P2D";

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
        $state = (new ChristmasTree(null, false))->dumpState();
        $code = $this->codeGenerator->generateCode();
        $tree = new TreeScene($code);
        $tree->setData($state);
        $tree->setPassword(null);
        $tree->setRevision(0);
        try {
            $this->store($tree, true, true);
        } catch (Exception $e) {
            return null;
        }
        return $tree;
    }

    public function store(TreeScene $tree, bool $shortValidity = false, bool $updateRevision = true): void
    {
        if ($tree->getRevision() > 1) {
            $shortValidity = false;
        }
        $validPeriod = $shortValidity ? self::INITIAL_VALID_PERIOD : self::VALID_PERIOD;
        if ($updateRevision) {
            $tree->setRevision($tree->getRevision() + 1);
        }
        $tree->updateValidTill((new DateTimeImmutable())->add(new DateInterval($validPeriod)));
        $this->entityManager->persist($tree);
        $this->entityManager->flush();
    }

    public function refresh(TreeScene $tree): void
    {
        $this->entityManager->refresh($tree);
    }

    public function getTerminalCode(TreeScene $tree, int $cols, int $rows): string
    {
        $christmasTree = new ChristmasTree($tree->getData());
        $buffer = new Output\BufferOutput($cols, $rows);
        $christmasTree->clearOutput($buffer);
        $christmasTree->render($buffer);
        return $buffer->getContent(true);
    }

    public function invokeStateChange(TreeScene $tree, callable $callback): void
    {
        $christmasTree = new ChristmasTree($tree->getData(), false);
        $write = $callback($christmasTree);
        if ($write) {
            $tree->setData($christmasTree->dumpState());
            $this->store($tree, false, true);
        }
    }
}
