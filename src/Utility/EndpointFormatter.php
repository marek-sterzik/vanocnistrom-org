<?php

namespace App\Utility;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface as UrlGenerator;

class EndpointFormatter
{
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
            array_map(function ($item){
                return sprintf("{%s}", $item);
            }, $params)
        );

        $uri = preg_replace('/\?.*$/', '', $uri);
        $uri = preg_replace('/%7B/i', '<span class="param">{', $uri);
        $uri = preg_replace('/%7D/i', '}</span>', $uri);

        return sprintf(
            "<span class=\"method-container\"><span class=\"method %s\">%s</span></span> <span class=\"uri\">%s</span>",
            htmlspecialchars(strtolower($method)),
            htmlspecialchars(strtoupper($method)),
            $uri
        );
    }
}
