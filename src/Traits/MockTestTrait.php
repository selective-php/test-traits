<?php

namespace Selective\TestTrait\Traits;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

/**
 * Mock Test Trait.
 */
trait MockTestTrait
{
    /**
     * Add mock to container.
     *
     * @param string $class The class or interface
     *
     * @throws InvalidArgumentException
     *
     * @return MockObject The mock
     */
    protected function mock(string $class): MockObject
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException(sprintf('Class not found: %s', $class));
        }

        $mock = $this->getMockBuilder($class)
            ->disableOriginalConstructor()
            ->getMock();

        if ($this->container instanceof ContainerInterface && method_exists($this->container, 'set')) {
            $this->container->set($class, $mock);
        }

        return $mock;
    }
}
