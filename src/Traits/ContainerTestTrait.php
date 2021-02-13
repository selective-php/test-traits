<?php

namespace Selective\TestTrait\Traits;

use BadMethodCallException;
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

    /**
     * Define an object or a value in the container.
     *
     * @param string $name Entry name
     * @param mixed $value Value, use definition helpers to define objects
     *
     * @throws BadMethodCallException
     *
     * @return void
     */
    protected function setContainerValue(string $name, $value): void
    {
        if (method_exists($this->container, 'set')) {
            $this->container->set($name, $value);
        }

        throw new BadMethodCallException('This DI container does not support this feature');
    }
}
