<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


use App\Entity\TreeScene;
use App\Tree\ChristmasTree;
use Exception;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
#[Route("/api/{tree}")]
class ApiController extends AbstractController
{
    const MAPPING = [
        "star" => ["type" => "single"],
        "chain" => ["type" => "multi"],
        "glassBalls" => ["type" => "multi"],
        "sweets" => ["type" => "multi"],
        "lamps" => ["type" => "multi"],
        "gifts" => ["type" => "gifts"],
        "scene" => ["type" => "scene"],
    ];

    const INVOCATION_TYPES = [
        "single" => [
            "GET" => "invokeGet",
            "PUT" => "invokePut",
            "DELETE" => "invokeDelete",
        ],
        "multi" => [
            "GET" => "invokeGetMulti",
            "PUT" => "invokePutMulti",
            "DELETE" => "invokeDeleteMulti",
        ],
        "gifts" => [
            "GET" => "invokeGetGifts",
            "PUT" => "invokePutGifts",
            "DELETE" => "invokeDeleteGifts",
        ],
        "scene" => [
            "DELETE" => "invokeDeleteScene",
        ],
    ];

    const PREPARE_METHODS = [
        "GET" => "prepareGet",
        "PUT" => "preparePut",
        "DELETE" => "prepareDelete",
    ];

    const COLORS = [
        "black" => 0,
        "red" => 1,
        "green" => 2,
        "yellow" => 3,
        "blue" => 4,
        "magenta" => 5,
        "cyan" => 6,
        "lightgray" => 7,
        "gray" => 8,
        "ligthred" => 9,
        "lightgreen" => 10,
        "lightyellow" => 11,
        "lightblue" => 12,
        "ligthmagenta" => 13,
        "lightcyan" => 14,
        "white" => 15,
        "default" => null,
    ];

    #[Route("", name: "api.scene")]
    #[Route("/", name: "api.scene.alt")]
    #[Route("/star", name: "api.star")]
    #[Route("/chains", name: "api.chain.collection")]
    #[Route("/chains/{fragment}", name: "api.chain.fragment")]
    #[Route("/glass-balls", name: "api.glassBalls.collection")]
    #[Route("/glass-balls/{fragment}", name: "api.glassBalls.fragment")]
    #[Route("/sweets", name: "api.sweets.collection")]
    #[Route("/sweets/{fragment}", name: "api.sweets.fragment")]
    #[Route("/lamps", name: "api.lamps.collection")]
    #[Route("/lamps/{fragment}", name: "api.lamps.fragment")]
    #[Route("/gifts", name: "api.gifts.collection")]
    #[Route("/gifts/{fragment}", name: "api.gifts.fragment")]
    public function invokeEndpoint(?TreeScene $tree, Request $request): Response
    {
        $route = $request->get('_route');
        $route = explode(".", $route, 3);
        if (($route[0] ?? null) !== 'api' || count($route) < 2) {
            return $this->getErrorResponse(400, "Bad request");
        }
        $route = $route[1];
        if (!isset(self::MAPPING[$route])) {
            return $this->getErrorResponse(400, "Bad request");
        }
        $endpointDescriptor = self::MAPPING[$route];
        $endpointDescriptor['route'] = $route;
        $method = $request->getMethod();
        if ($method === "GET") {
            $queryMethod = $request->query->get("method");
            if ($queryMethod !== null) {
                if (!is_string($queryMethod)) {
                    return $this->getErrorResponse(400, "Bad request");
                }
                $queryMethod = strtoupper($queryMethod);
                $method = $queryMethod;
            }
        }
        $params = $this->prepare($request, $method, $endpointDescriptor);
        if ($params === null) {
            return $this->getErrorResponse(400, "Bad request");
        }

        if ($tree === null) {
            $tree = $this->findProperTree();
            if ($tree === null) {
                return $this->getErrorResponse(404, "Christmas tree not found");
            }
        }

        return $this->invoke($params, $tree);
    }

    private function prepare(Request $request, string $method, array $descriptor): ?array
    {
        if (!isset(self::PREPARE_METHODS[$method])) {
            return null;
        }
        $fragment = $this->getFragment($request);
        if ($fragment === false) {
            return null;
        }
        $prepareMethod = self::PREPARE_METHODS[$method];
        $params = $this->$prepareMethod($request, $descriptor, $fragment);
        if ($params === null) {
            return null;
        }
        $params['method'] = self::INVOCATION_TYPES[$descriptor['type']][$method] ?? null;
        if ($params['method'] === null) {
            return null;
        }
        $params['fragment'] = $fragment;
        return $params;
    }

    private function getGetter(array $descriptor, ?int $fragment): string
    {
        return $descriptor['get'] ?? ('get' . ucfirst($descriptor['route']));
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function prepareGet(Request $request, array $descriptor, ?int $fragment): array
    {
        $treeMethod = $this->getGetter($descriptor, $fragment);
        return [
            "treeMethod" => $treeMethod,
            "getter" => $treeMethod,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function prepareDelete(Request $request, array $descriptor, ?int $fragment): array
    {
        $getter = $this->getGetter($descriptor, $fragment);
        if ($fragment === null) {
            $treeMethod = $descriptor['delete'] ?? ('remove' . ucfirst($descriptor['route']));
        } else {
            $treeMethod = $descriptor['deleteFragment'] ?? ('remove' . ucfirst($descriptor['route']) . "Part");
        }
        return [
            "treeMethod" => $treeMethod,
            "getter" => $getter,
        ];
    }

    private function getRequestData(Request $request): array
    {
        $body = [];
        if ($request->getMethod() === 'PUT') {
            try {
                $body = $request->toArray();
            } catch (Exception $e) {
                $body = [];
            }
        }
        $get = ["label" => "label", "color" => "color", "labelColor" => "label-color"];
        $data = [];
        foreach ($get as $key => $getKey) {
            if (array_key_exists($key, $body)) {
                $data[$key] = $body[$key];
            } elseif (array_key_exists($getKey, $body)) {
                $data[$key] = $body[$getKey];
            } else {
                $data[$key] = $request->query->get($getKey) ?? $request->query->get($key);
            }
        }
        return $data;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function preparePut(Request $request, array $descriptor, ?int $fragment): ?array
    {
        $getter = $this->getGetter($descriptor, $fragment);
        if ($fragment === null) {
            $treeMethod = $descriptor['put'] ?? ('put' . ucfirst($descriptor['route']));
        } else {
            $treeMethod = $descriptor['putFragment'] ?? ('put' . ucfirst($descriptor['route']) . "Part");
        }
        $requestData = $this->getRequestData($request);
        $color = $this->parseColor($requestData["color"]);
        if ($color === false) {
            return null;
        }
        $labelColor = $this->parseColor($requestData["labelColor"]);
        if ($labelColor === false) {
            $labelColor = null;
            $labelColorValid = false;
        } else {
            $labelColorValid = true;
        }

        $label = $requestData["label"];
        if ($label !== null && !is_string($label)) {
            $labelValid = false;
            $label = null;
        } else {
            $labelValid = true;
        }
        return [
            "treeMethod" => $treeMethod,
            "getter" => $getter,
            "color" => $color,
            "labelColor" => $labelColor,
            "labelColorValid" => $labelColorValid,
            "label" => $label,
            "labelValid" => $labelValid,
        ];
    }

    private function getFragment(Request $request): int|false|null
    {
        $fragment = $request->attributes->get("fragment");
        if ($fragment === null) {
            return null;
        } else {
            if (!is_string($fragment) || !preg_match('/^[0-9]+$/', $fragment)) {
                return false;
            }
            return (int)$fragment;
        }
    }

    private function invoke(array $params, TreeScene $tree): Response
    {
        $method = $params['method'];
        $treeMethod = $params['treeMethod'] ?? null;
        if ($treeMethod === null) {
            return $this->getErrorResponse(405, "Method not allowed");
        }
        return $this->$method($params, $treeMethod, $tree);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function invokeGet(array $params, string $method, TreeScene $tree): Response
    {
        $state = null;
        $this->treeManager->invokeStateChange($tree, function (ChristmasTree $christmasTree) use (&$state, $method) {
            $state = $this->colorToApi($christmasTree->$method());
            return false;
        });
        return $this->getSuccessResponse($state);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function invokePut(array $params, string $method, TreeScene $tree): Response
    {
        $color = $params['color'];
        $this->treeManager->invokeStateChange($tree, function (ChristmasTree $christmasTree) use ($color, $method) {
            $christmasTree->$method($color);
            return true;
        });
        return $this->getSuccessResponse(["success" => true]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function invokeDelete(array $params, string $method, TreeScene $tree): Response
    {
        $this->treeManager->invokeStateChange($tree, function (ChristmasTree $christmasTree) use ($method) {
            $christmasTree->$method();
            return true;
        });
        return $this->getSuccessResponse(["success" => true]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function invokeGetMulti(array $params, string $method, TreeScene $tree): Response
    {
        $state = null;
        $fragment = $params['fragment'];
        $this->treeManager->invokeStateChange(
            $tree,
            function (ChristmasTree $christmasTree) use (&$state, $method, $fragment) {
                $stateAll = $christmasTree->$method();
                if ($fragment !== null) {
                    if (isset($stateAll[$fragment])) {
                        $state = array_merge(["id" => $fragment], $this->colorToApi($stateAll[$fragment]));
                    }
                } else {
                    $state = [];
                    foreach ($stateAll as $index => $color) {
                        $state[] = array_merge(["id" => $index], $this->colorToApi($color));
                    }
                }
                return false;
            }
        );
        if ($state === null) {
            return $this->getErrorResponse(404, "Fragment not found");
        }
        return $this->getSuccessResponse($state);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function invokePutMulti(array $params, string $method, TreeScene $tree): Response
    {
        $color = $params['color'];
        $fragment = $params['fragment'];
        $getter = $params['getter'];
        $success = false;
        $this->treeManager->invokeStateChange(
            $tree,
            function (ChristmasTree $christmasTree) use (&$success, $color, $method, $getter, $fragment) {
                if ($fragment === null) {
                    $christmasTree->$method($color);
                    $success = true;
                } else {
                    $state = $christmasTree->$getter();
                    if (isset($state[$fragment])) {
                        $christmasTree->$method($fragment, $color);
                        $success = true;
                    }
                }
                return $success;
            }
        );
        if (!$success) {
            return $this->getErrorResponse(404, "Fragment not found");
        }
        return $this->getSuccessResponse(["success" => true]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function invokeDeleteMulti(array $params, string $method, TreeScene $tree): Response
    {
        $fragment = $params['fragment'];
        $getter = $params['getter'];
        $success = false;
        $this->treeManager->invokeStateChange(
            $tree,
            function (ChristmasTree $christmasTree) use (&$success, $method, $getter, $fragment) {
                if ($fragment === null) {
                    $christmasTree->$method();
                    $success = true;
                } else {
                    $state = $christmasTree->$getter();
                    if (isset($state[$fragment])) {
                        $christmasTree->$method($fragment);
                        $success = true;
                    }
                }
                return $success;
            }
        );
        if (!$success) {
            return $this->getErrorResponse(404, "Fragment not found");
        }
        return $this->getSuccessResponse(["success" => true]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function invokeGetGifts(array $params, string $method, TreeScene $tree): Response
    {
        $state = null;
        $fragment = $params['fragment'];
        $this->treeManager->invokeStateChange(
            $tree,
            function (ChristmasTree $christmasTree) use (&$state, $method, $fragment) {
                $stateAll = $christmasTree->$method();
                if ($fragment !== null) {
                    if (isset($stateAll[$fragment])) {
                        $state = $this->giftToApi($fragment, $stateAll[$fragment]);
                    }
                } else {
                    $state = [];
                    foreach ($stateAll as $index => $stateOne) {
                        $state[] = $this->giftToApi($index, $stateOne);
                    }
                }
                return false;
            }
        );
        if ($state === null) {
            return $this->getErrorResponse(404, "Gift not found");
        }
        return $this->getSuccessResponse($state);
    }

    private function getGiftColor(int|false|null $color): string
    {
        $info = $this->colorToApi($color);
        return $info['color'] ?? 'none';
    }

    private function giftToApi(int $fragment, array $data): array
    {
        $packageColor = $this->getGiftColor($data['packageColor']);
        $labelColor = $this->getGiftColor($data['labelColor']);
        return [
            "id" => $fragment,
            "packageColor" => $packageColor,
            "labelColor" => $labelColor,
            "label" => $data['label'],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function invokePutGifts(array $params, string $method, TreeScene $tree): Response
    {
        if (!$params['labelColorValid'] || !$params['labelValid']) {
            return $this->getErrorResponse(400, "Bad request");
        }
        $fragment = $params['fragment'];
        $getter = $params['getter'];
        $gift = [
            'label' => $params['label'] ?? '',
            'packageColor' => $params['color'],
            'labelColor' => $params['labelColor'] ?? $params['color'],
        ];
        $success = false;
        $this->treeManager->invokeStateChange(
            $tree,
            function (ChristmasTree $christmasTree) use (&$success, $gift, $method, $getter, $fragment) {
                if ($fragment === null) {
                    $christmasTree->$method($gift['label'], $gift['packageColor'], $gift['labelColor']);
                    $success = true;
                } else {
                    $state = $christmasTree->$getter();
                    if (isset($state[$fragment]) || $fragment === count($state)) {
                        $christmasTree->$method($fragment, $gift['label'], $gift['packageColor'], $gift['labelColor']);
                        $success = true;
                    }
                }
                return $success;
            }
        );
        if (!$success) {
            return $this->getErrorResponse(404, "Gift not found");
        }
        return $this->getSuccessResponse(["success" => true]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function invokeDeleteGifts(array $params, string $method, TreeScene $tree): Response
    {
        $fragment = $params['fragment'];
        $getter = $params['getter'];
        $success = false;
        $this->treeManager->invokeStateChange(
            $tree,
            function (ChristmasTree $christmasTree) use (&$success, $method, $getter, $fragment) {
                if ($fragment === null) {
                    $christmasTree->$method();
                    $success = true;
                } else {
                    $state = $christmasTree->$getter();
                    if (isset($state[$fragment])) {
                        $christmasTree->$method($fragment);
                        $success = true;
                    }
                }
                return $success;
            }
        );
        if (!$success) {
            return $this->getErrorResponse(404, "Gift not found");
        }
        return $this->getSuccessResponse(["success" => true]);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function invokeDeleteScene(array $params, string $method, TreeScene $tree): Response
    {
        $tree->setData([]);
        $this->treeManager->store($tree, true, true);
        return $this->getSuccessResponse(["success" => true]);
    }

    /** @phpstan-ignore-next-line */
    private function colorToApi(int|false|null $color): ?array
    {
        if ($color === false) {
            return ["exists" => false];
        }
        foreach (self::COLORS as $colorString => $value) {
            if ($color === $value) {
                return ["exists" => true, "color" => $colorString];
            }
        }
        return ["exists" => true, "color" => "default"];
    }

    private function parseColor(mixed $color): int|false|null
    {
        if ($color === null) {
            return null;
        }
        if (is_int($color)) {
            if ($color < 0 || $color > 15) {
                return false;
            }
            return $color;
        }
        if (!is_string($color)) {
            return false;
        }
        if (preg_match('/^[0-9]+$/', $color)) {
            $color = (int)$color;
            if ($color < 0 || $color > 15) {
                return false;
            }
            return $color;
        }
        $color = strtolower($color);
        if (!array_key_exists($color, self::COLORS)) {
            return false;
        }
        return self::COLORS[$color];
    }

    private function getSuccessResponse(array $data): Response
    {
        return new JsonResponse($data, 200);
    }

    private function getErrorResponse(int $code, string $message): Response
    {
        return new JsonResponse(["error" => $message, "errorCode" => $code], $code);
    }
}
