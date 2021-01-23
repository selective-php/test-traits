<?php

namespace Selective\TestTrait\Traits;

use DI\Container;
use UnexpectedValueException;

/**
 * Container Test Trait.
 */
trait ContainerTestTrait
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Bootstrap app.
     *
     * TestCases must call this method inside setUp().
     *
     * @param Container|null $container The container
     *
     * @throws UnexpectedValueException
     *
     * @return void
     */
    protected function setUpContainer(Container $container = null): void
    {
        if ($container instanceof Container) {
            $this->container = $container;

            return;
        }

        throw new UnexpectedValueException('Container must be instance of DI\Container');
    }
}
