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
    const DOC_LANGUAGES = [
        "en" => ["en"],
        "cz" => ["cs"],
    ];

    public function __construct(protected TreeManager $treeManager, protected RequestStack $requestStack)
    {
    }

    private function detectLanguage(Request $request): string
    {
        $acceptedLanguages = $request->headers->get("Accept-Language");
        $acceptedLanguages = is_string($acceptedLanguages) ? explode(",", $acceptedLanguages) : [];
        $acceptedLanguages = array_map(fn ($lng) => trim(preg_replace('/;.*$/', '', $lng)), $acceptedLanguages);
        $acceptedLanguages = array_map(fn ($lng) => preg_replace('/[_\\-].*$/', '', $lng), $acceptedLanguages);
        foreach ($acceptedLanguages as $acceptedLanguage) {
            foreach (self::DOC_LANGUAGES as $lang => $detect) {
                foreach ($detect as $detectLang) {
                    if ($detectLang === $acceptedLanguage) {
                        return $lang;
                    }
                }
            }
        }
        return array_key_first(self::DOC_LANGUAGES);
    }

    protected function getLanguageInfo(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $lang = $request->query->get("lang");
        $strict = true;

        if (!is_string($lang) || !isset(self::DOC_LANGUAGES[$lang])) {
            $lang = $this->detectLanguage($request);
            $strict = false;
        }

        $languages = array_map(
            fn ($lng) => [
                "code" => $lng,
                "selected" => ($lng === $lang),
                "url" => "?lang=" . urlencode($lng),
            ],
            array_keys(self::DOC_LANGUAGES)
        );
        return [
            "selectedLanguage" => $lang,
            "selectedLanguageStrict" => $strict,
            "languages" => $languages,
        ];
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

    protected function treeNotFound(): Response
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
