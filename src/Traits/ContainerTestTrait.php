<?php

namespace Selective\TestTrait\Traits;

use Psr\Container\ContainerInterface;
use UnexpectedValueException;

/**
 * Container Test Trait.
 */
trait ContainerTestTrait
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Setup DI container.
     *
     * TestCases must call this method inside setUp().
     *
     * @param ContainerInterface|null $container The container
     *
     * @throws UnexpectedValueException
     *
     * @return void
     */
    protected function setUpContainer(ContainerInterface $container = null): void
    {
        if ($container instanceof ContainerInterface) {
            $this->container = $container;

            return;
        }

        throw new UnexpectedValueException('Container must be initialized');
    }
}
