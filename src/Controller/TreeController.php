<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Entity\TreeScene;

#[Route("/{tree}")]
class TreeController extends AbstractController
{
    public const MAX_TRIES = 30;

    #[Route("", name: "tree")]
    #[Route("/", name: "tree.secondary")]
    public function showTree(?TreeScene $tree): Response
    {
        if ($tree === null) {
            return $this->treeNotFound();
        }

        $treeUrl = $this->generateUrl('tree', ["tree" => $tree->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $treeUrlShow = preg_replace('|https?://|', '', $treeUrl);
        
        $this->treeManager->store($tree, true, false);
        $this->treeManager->cleanup();
        return $this->render('main.html.twig', [
            "treeUrl" => $treeUrl,
            "treeUrlShow" => $treeUrlShow,
            "tree" => $tree->getId(),
            "terminalConfig" => [
                "optimalFit" => [120, 40],
                "minimalFit" => [60, 40],
                "baseFontSize" => 17,
                "resetOnResize" => true,
                "dataEndpoint" => $this->generateUrl("tree.data", ["tree" => $tree->getId()]),
                "errorTimeout" => 5000,
                "noDataTimeout" => 1000,
            ],
        ]);
    }

    #[Route("/api", name: "tree.api")]
    public function showApi(?TreeScene $tree): Response
    {
        if ($tree === null) {
            return $this->treeNotFound();
        }

        $treeUrl = $this->generateUrl('tree', ["tree" => $tree->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $treeUrlShow = preg_replace('|https?://|', '', $treeUrl);

        $this->treeManager->store($tree, true, false);
        $this->treeManager->cleanup();

        return $this->render('api.html.twig', [
            "treeUrl" => $treeUrl,
            "treeUrlShow" => $treeUrlShow,
            "tree" => $tree->getId(),
        ]);
    }

    #[Route("/data", name: "tree.data")]
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
        $data = $this->treeManager->getTerminalCode($tree, $cols ?? 80, $rows ?? 25);
        $this->treeManager->store($tree, true, false);
        $this->treeManager->cleanup();
        return [
            "data" => $data,
            "revision" => $tree->getRevision(),
        ];
    }

    private function treeNotFound(): Response
    {
        $response = $this->render('error.html.twig', ["errorCode" => "tree_not_found"]);
        $response->setStatusCode(404);
        return $response;
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
