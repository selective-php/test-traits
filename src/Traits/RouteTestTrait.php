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
     * @param string[] $data Named argument replacement data
     * @param string[] $queryParams Optional query string parameters.
     * If you're using `nyholm/psr7`, query parameters MUST be added via
     * `$request = $request->withQueryParams($queryParams)`
     * to be retrieved with `$request->getQueryParams();`
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
