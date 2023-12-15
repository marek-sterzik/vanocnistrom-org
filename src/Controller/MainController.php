<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


use App\Entity\TreeScene;
use App\Tree\Manager as TreeManager;

class MainController extends AbstractController
{
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
            "treeId" => $tree->getId(),
        ]);
    }
}
