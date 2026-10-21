<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MainController extends AbstractController
{
    #[Route("/", name: "main")]
    public function index(Request $request): Response
    {
        $code = $request->query->get("tree", "");
        if (is_string($code) && $code != "") {
            $treeUrlBase = preg_replace('|https?://|', '', $this->generateUrl('main', [], UrlGeneratorInterface::ABSOLUTE_URL));
            $code = preg_replace('|^https?://|', '', $code);
            $code = $this->cutPrefix($code, $treeUrlBase);
            $tree = $this->treeManager->getTreeById($code);
            if ($tree !== null) {
                return $this->redirectToRoute("tree", ["tree" => $tree->getId()]);
            }
            return $this->treeNotFound();
            die("no tree");
        }
        $languageInfo = $this->getLanguageInfo();
        if (!$languageInfo['selectedLanguageStrict']) {
            return $this->redirect("?lang=" . urlencode($languageInfo['selectedLanguage']));
        }
        return $this->render("entrance.html.twig", [
            "languages" => $languageInfo['languages'],
            "selectedLanguage" => $languageInfo["selectedLanguage"],
        ]);
        return $this->redirectToRoute("create");
    }

    private function cutPrefix(string $string, string $prefix): string
    {
        if (strlen($prefix) <= strlen($string) && substr($string, 0, strlen($prefix)) === $prefix) {
            return substr($string, strlen($prefix));
        }
        return $string;
    }

    #[Route("/common/create", name: "create")]
    public function createTree(): Response
    {
        $this->treeManager->cleanup();
        $tree = $this->treeManager->createTree();
        if ($tree !== null) {
            return $this->redirectToRoute("tree", ["tree" => $tree->getId()]);
        } else {
            $response = $this->render('error.html.twig', [
                "errorCode" => "cannot_create_tree",
            ]);
            $response->setStatusCode(500);
            return $response;
        }
    }
}
