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
    const DOC_LANGUAGES = [
        "en" => ["en"],
        "cz" => ["cs"],
    ];

    #[Route("", name: "tree")]
    #[Route("/", name: "tree.secondary")]
    public function showTree(?TreeScene $tree): Response
    {
        $response = $this->checkTree($tree);
        if ($response !== null) {
            return $response;
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
    public function showApi(?TreeScene $tree, Request $request): Response
    {
        $response = $this->checkTree($tree);
        if ($response !== null) {
            return $response;
        }

        $lang = $request->query->get("lang");

        if (!is_string($lang) || !isset(self::DOC_LANGUAGES[$lang])) {
            $lang = $this->detectLanguage($request);
            return $this->redirectToRoute("tree.api", ["tree" => $tree->getId(), "lang" => $lang]);
        }

        $languages = array_map(
            fn ($l) => [
                "code" => $l,
                "selected" => ($l === $lang),
                "url" => $this->generateUrl('tree.api', ["tree" => $tree->getId(), "lang" => $l])
            ],
            array_keys(self::DOC_LANGUAGES)
        );

        $treeUrl = $this->generateUrl('tree', ["tree" => $tree->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $treeUrlShow = preg_replace('|https?://|', '', $treeUrl);

        $this->treeManager->store($tree, true, false);
        $this->treeManager->cleanup();

        return $this->render('api.html.twig', [
            "selectedLanguage" => $lang,
            "languages" => $languages,
            "treeUrl" => $treeUrl,
            "treeUrlShow" => $treeUrlShow,
            "tree" => $tree->getId(),
        ]);
    }

    private function detectLanguage(Request $request): string
    {
        $acceptedLanguages = $request->headers->get("Accept-Language");
        $acceptedLanguages = is_string($acceptedLanguages) ? explode(",", $acceptedLanguages) : [];
        $acceptedLanguages = array_map(fn ($l) => trim(preg_replace('/;.*$/', '', $l)), $acceptedLanguages);
        $acceptedLanguages = array_map(fn ($l) => preg_replace('/[_\\-].*$/', '', $l), $acceptedLanguages);
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

    private function parseInt(Request $request, string $getField): ?int
    {
        $value = $request->query->get($getField);
        if ($value === null || !is_string($value) || !preg_match('/^[0-9]+$/', $value)) {
            return null;
        }
        return (int)$value;
    }
}
