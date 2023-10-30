<?php

namespace Selective\TestTrait\Traits;

use Slim\App;

/**
 * Slim App Route Test Trait.
 */
trait RouteTestTrait
{
    /**
     * Build the path for a named route including the base path.
     *
     * @param string $routeName Route name
     * @param array<string, string> $data Named argument replacement data
     * @param array<string, string> $queryParams Optional query string parameters
     *
     * @return string The route with base path
     */
    protected function urlFor(string $routeName, array $data = [], array $queryParams = []): string
    {
        return $this->container->get(App::class)
            ->getRouteCollector()
            ->getRouteParser()
            ->urlFor($routeName, $data, $queryParams);
    }
}
