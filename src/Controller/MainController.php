<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route("/", name: "main")]
    public function index(): Response
    {
        $this->treeManager->cleanup();
        return $this->redirectToRoute("create");
    }

    #[Route("/common/create", name: "create")]
    public function createTree(): Response
    {
        $this->treeManager->cleanup();
        $tree = $this->treeManager->createTree();
        if ($tree !== null) {
            return $this->redirectToRoute("tree", ["tree" => $tree->getId()]);
        } else {
            return $this->render('error.html.twig', [
                "errorCode" => "cannot_create_tree",
            ]);
        }
    }
}
