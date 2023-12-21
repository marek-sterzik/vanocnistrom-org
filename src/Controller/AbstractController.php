<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as AbstractControllerBase;

use App\Tree\Manager as TreeManager;
use App\Entity\TreeScene;

class AbstractController extends AbstractControllerBase
{
    public function __construct(protected TreeManager $treeManager, protected RequestStack $requestStack)
    {
    }

    protected function findProperTree(): ?TreeScene
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }
        $routeParams = $request->get('_route_params');
        if (!isset($routeParams['tree']) || !is_string($routeParams['tree'])) {
            return null;
        }
        return $this->treeManager->getTreeById($routeParams['tree']);
    }

    protected function redirectToProperTree(): ?Response
    {
        $tree = $this->findProperTree();

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }
        $route = $request->get('_route');
        $routeParams = $request->get('_route_params');
        if ($tree === null || !isset($routeParams['tree'])) {
            return null;
        }
        $routeParams['tree'] = $tree->getId();
        return $this->redirectToRoute($route, $routeParams);
    }

    protected function checkTree(?TreeScene $tree): ?Response
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $route = $request->get("_route");
            $routeParams = $request->get("_route_params");
            if ($routeParams !== null && isset($routeParams['tree']) && $tree !== null) {
                if ($tree->getId() !== $routeParams['tree']) {
                    $routeParams['tree'] = $tree->getId();
                    return $this->redirectToRoute($route, $routeParams);
                }
            }
        }
        if ($tree === null) {
            return $this->treeNotFound();
        }
        return null;
    }

    private function treeNotFound(): Response
    {
        $response = $this->redirectToProperTree();
        if ($response !== null) {
            return $response;
        }
        $response = $this->render('error.html.twig', ["errorCode" => "tree_not_found"]);
        $response->setStatusCode(404);
        return $response;
    }
}
