<?php

namespace Selective\TestTrait\Traits;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Factory\ServerRequestFactory;

/**
 * HTTP Test Trait.
 */
trait HttpTestTrait
{
    /**
     * Create a server request.
     *
     * @param string $method The HTTP method
     * @param string|UriInterface $uri The URI
     * @param array<mixed> $serverParams The server parameters
     *
     * @return ServerRequestInterface The request
     */
    protected function createRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest($method, $uri, $serverParams);
    }

    /**
     * Create a form request.
     *
     * @param string $method The HTTP method
     * @param string|UriInterface $uri The URI
     * @param array<mixed>|null $data The form data
     *
     * @return ServerRequestInterface
     */
    protected function createFormRequest(string $method, $uri, array $data = null): ServerRequestInterface
    {
        $request = $this->createRequest($method, $uri);

        if ($data !== null) {
            $request = $request->withParsedBody($data);
        }

        return $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    /**
     * Build the path for a named route including the base path.
     *
     * @param string $routeName Route name
     * @param string[] $data Named argument replacement data
     * @param string[] $queryParams Optional query string parameters
     *
     * @return string route with base path
     */
    protected function urlFor(string $routeName, array $data = [], array $queryParams = []): string
    {
        return $this->app->getRouteCollector()->getRouteParser()->urlFor($routeName, $data, $queryParams);
    }
}
