<?php

namespace Selective\TestTrait\Traits;

/**
 * Array Test Trait.
 */
trait ArrayTestTrait
{
    /**
     * Read array value with dot notation.
     *
     * @param array $data The array
     * @param string $path The path
     * @param null $default The default return value#
     *
     * @return mixed|null The value from the array or the default value
     */
    protected function getArrayValue(array $data, string $path, $default = null)
    {
        $parts = explode('.', $path);

        switch (count($parts)) {
            case 1:
                return isset($data[$parts[0]]) ? $data[$parts[0]] : $default;
            case 2:
                return isset($data[$parts[0]][$parts[1]]) ? $data[$parts[0]][$parts[1]] : $default;
            case 3:
                return isset($data[$parts[0]][$parts[1]][$parts[2]]) ? $data[$parts[0]][$parts[1]][$parts[2]] : $default;
            default:
                foreach ($parts as $key) {
                    if ((is_array($data)) && isset($data[$key])) {
                        $data = $data[$key];
                    } else {
                        return $default;
                    }
                }
        }
    }
}
