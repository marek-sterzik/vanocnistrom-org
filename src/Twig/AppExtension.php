<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Markup;

use App\Utility\EndpointFormatter;

class AppExtension extends AbstractExtension
{
    public function __construct(private EndpointFormatter $endpointFormatter) {
    }

    public function getFilters(): array
    {
        return [];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('endpoint', [$this, 'formatEndpoint'], ['is_safe' => ['all']]),
        ];
    }

    public function formatEndpoint(string $method, string $routeName, array $params = []): string
    {
        return $this->endpointFormatter->formatEndpoint($method, $routeName, $params);
    }
}
