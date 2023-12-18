<?php

namespace App\Utility;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface as UrlGenerator;
use Exception;

class EndpointFormatter
{
    const API_PREFIX = "/api";

    public function __construct(private UrlGenerator $router)
    {
    }

    public function formatEndpoint(string $method, string $routeName, array $params = []): string
    {
        $uriFormatted = $routeName;

        if (!isset($params['tree'])) {
            $params['tree'] = 'tree';
        }

        $uri = $this->router->generate(
            $routeName,
            array_map(function ($item) {
                return sprintf("{%s}", $item);
            }, $params)
        );

        $uri = preg_replace('/\?.*$/', '', $uri);
        $uri = preg_replace('/%7B/i', '<span class="param">{', $uri);
        $uri = preg_replace('/%7D/i', '}</span>', $uri);
        if (substr($uri, 0, strlen(self::API_PREFIX)) !== self::API_PREFIX) {
            throw new Exception("Invalid endpoint out of API prefix scope");
        }
        $uri = substr($uri, strlen(self::API_PREFIX));

        return sprintf(
            "<span class=\"method-container\"><span class=\"method %s\">%s</span></span> <span class=\"uri\">%s</span>",
            htmlspecialchars(strtolower($method)),
            htmlspecialchars(strtoupper($method)),
            $uri
        );
    }

    public function getEndpointBase(): string
    {
        $url = $this->router->generate("main", [], UrlGenerator::ABSOLUTE_URL);
        $url = rtrim($url, "/") . self::API_PREFIX . "/";
        return $url;
    }
}
