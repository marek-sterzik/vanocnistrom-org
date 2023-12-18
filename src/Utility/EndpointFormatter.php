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

    public function formatEndpoint(
        string $method,
        string $routeName,
        array $params = [],
        bool $fillParams = false,
        array|string|null $queryString = null
    ): string {
        if (!$fillParams) {
            $params = array_map(function ($item) {
                return sprintf("{%s}", $item);
            }, $params);
        }

        $uri = $this->router->generate(
            $routeName,
            $params
        );

        $uri = preg_replace('/\?.*$/', '', $uri);
        if (!$fillParams) {
            $uri = preg_replace('/%7B/i', '<span class="param">{', $uri);
            $uri = preg_replace('/%7D/i', '}</span>', $uri);
        }
        if (substr($uri, 0, strlen(self::API_PREFIX)) !== self::API_PREFIX) {
            throw new Exception("Invalid endpoint out of API prefix scope");
        }
        $uri = substr($uri, strlen(self::API_PREFIX));
        if ($queryString !== null) {
            $uri .= $this->formatQueryString($queryString);
        }

        return sprintf(
            "<span class=\"method-container\"><span class=\"method %s\">%s</span></span> <span class=\"uri\">%s</span>",
            htmlspecialchars(strtolower($method)),
            htmlspecialchars(strtoupper($method)),
            $uri
        );
    }

    private function formatQueryString(array|string|null $queryString): string
    {
        if ($queryString === null) {
            return '';
        }
        if (is_array($queryString)) {
            $str = '';
            foreach ($queryString as $index => $value) {
                if ($str !== '') {
                    $str .= "&";
                }
                $str .= urlencode($index) . "=" . urlencode($value);
            }
            $queryString = $str;
        }
        return "?" . $queryString;
    }

    public function getEndpointBase(): string
    {
        $url = $this->router->generate("main", [], UrlGenerator::ABSOLUTE_URL);
        $url = rtrim($url, "/") . self::API_PREFIX . "/";
        return $url;
    }
}
