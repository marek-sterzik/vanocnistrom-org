<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


use App\Entity\TreeScene;
use App\Tree\Manager as TreeManager;

class MainController extends AbstractController
{
    public const MAX_TRIES = 30;

    public function __construct(private TreeManager $treeManager)
    {
    }

    #[Route("/", name: "main")]
    public function index(): Response
    {
        return $this->redirectToRoute("create");
    }

    #[Route("/common/create", name: "create")]
    public function createTree(): Response
    {
        $tree = $this->treeManager->createTree();
        if ($tree !== null) {
            return $this->redirectToRoute("tree", ["tree" => $tree->getId()]);
        } else {
            return $this->render('error.html.twig', [
                "errorCode" => "cannot_create_tree",
            ]);
        }
    }

    #[Route("/{tree}", name: "tree")]
    public function showTree(?TreeScene $tree): Response
    {
        if ($tree === null) {
            return $this->render('error.html.twig', ["errorCode" => "tree_not_found"]);
        }

        $treeUrl = $this->generateUrl('tree', ["tree" => $tree->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $treeUrlShow = preg_replace('|https?://|', '', $treeUrl);
        return $this->render('main.html.twig', [
            "treeUrl" => $treeUrl,
            "treeUrlShow" => $treeUrlShow,
            "terminalConfig" => [
                "optimalFit" => [120, 40],
                "minimalFit" => [80, 25],
                "baseFontSize" => 17,
                "resetOnResize" => true,
                "dataEndpoint" => $this->generateUrl("tree.data", ["tree" => $tree->getId()]),
                "errorTimeout" => 5000,
                "noDataTimeout" => 1000,
            ],
        ]);
    }

    #[Route("/{tree}/data", name: "tree.data")]
    public function treeData(?TreeScene $tree, Request $request): array
    {
        if ($tree === null) {
            return [
                "data" => null,
                "revision" => null,
            ];
        }
        $revision = $this->parseInt($request, "revision");
        $cols = $this->parseInt($request, "cols");
        $rows = $this->parseInt($request, "rows");
        $tries = self::MAX_TRIES;
        while ($revision !== null && $tree->getRevision() === $revision) {
            $tries--;
            if ($tries == 0) {
                return [
                    "data" => null,
                    "revision" => $tree->getRevision(),
                ];
            }
            sleep(1);
            $this->treeManager->refresh($tree);

        }
        $data = "Hello from \x1B[1;3;31mxterm.js\x1B[0m $\n\r";
        $data .= "at revision " . $tree->getRevision() . "\n\r";
        
        return [
            "data" => $data,
            "revision" => $tree->getRevision(),
        ];
    }

    private function parseInt(Request $request, string $getField): ?int
    {
        $value = $request->query->get($getField);
        if ($value === null || !is_string($value) || !preg_match('/^[0-9]+$/', $value)) {
            return null;
        }
        return (int)$value;
    }
}
