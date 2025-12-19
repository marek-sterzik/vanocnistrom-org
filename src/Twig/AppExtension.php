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
            new TwigFunction('endpoint_base', [$this, 'getEndpointBase']),
            new TwigFunction('load_resource', [$this, 'loadResource']),
            new TwigFunction('tree_id', [$this, 'getTreeId']),
            new TwigFunction('curl_command', [$this, 'getCurlCommand']),
            new TwigFunction('test_url', [$this, 'getTestUrl']),
        ];
    }

    public function formatEndpoint(
        string $method,
        string $routeName,
        array $params = [],
        bool $fillParams = false,
        array|string|null $queryString = null,
        bool $fullUrl = false
    ): string {
        if (!isset($params['tree'])) {
            $params['tree'] = $fillParams ? $this->getTreeId() : 'tree';
        }
        return $this->endpointFormatter->formatEndpoint(
            $method,
            $routeName,
            $params,
            $fillParams,
            $queryString,
            $fullUrl
        );
    }

    public function getEndpointBase(): string
    {
        return $this->endpointFormatter->getEndpointBase();
    }

    public function loadResource(string $resource, string $language): array
    {
        return $this->resourceLoader->loadResource($resource, null, $language);
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

    public function getCurlCommand(
        string $method,
        string $route,
        array $params,
        array $paramsData,
        ?array $request
    ): string {
        return $this->endpointFormatter->getCurlCommand(
            $method,
            $route,
            $params,
            $paramsData,
            $request,
            $this->getTreeId()
        );
    }

    public function getTestUrl(
        string $method,
        string $route,
        array $params,
        array $paramsData,
        ?array $request
    ): string {
        $url = $this->endpointFormatter->getTestUrl(
            $method,
            $route,
            $params,
            $paramsData,
            $request,
            $this->getTreeId()
        );
        $request = [
            ... (($method === 'GET') ? [] : ['method' => $method]),
            ... ($request ?? [])
        ];
        if (!empty($request)) {
            $url = $url . "?" . http_build_query($request);
        }
        return $url;
    }
}
