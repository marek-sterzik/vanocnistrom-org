<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Markup;

use App\Utility\EndpointFormatter;
use App\Utility\ResourceLoader;

class AppExtension extends AbstractExtension
{
    public function __construct(private EndpointFormatter $endpointFormatter, private ResourceLoader $resourceLoader)
    {
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
}
