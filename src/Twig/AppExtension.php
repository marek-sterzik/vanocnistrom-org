<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Markup;

use App\Utility\EndpointFormatter;
use App\Utility\ResourceLoader;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private EndpointFormatter $endpointFormatter,
        private ResourceLoader $resourceLoader,
        private RequestStack $requestStack,
    ) {
    }

    public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('endpoint', [$this, 'formatEndpoint'], ['is_safe' => ['all']]),
            new TwigFunction('load_resource', [$this, 'loadResource']),
            new TwigFunction('tree_id', [$this, 'getTreeId']),
        ];
    }

    public function formatEndpoint(string $method, string $routeName, array $params = []): string
    {
        return $this->endpointFormatter->formatEndpoint($method, $routeName, $params);
    }

    public function loadResource(string $resource): array
    {
        return $this->resourceLoader->loadResource($resource);
    }

    public function getTreeId(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }
        $routeParams = $request->attributes->get("_route_params");
        if (!is_array($routeParams) || !isset($routeParams['tree'])) {
            return null;
        }
        return $routeParams['tree'];
    }
}
