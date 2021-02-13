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
     * @return mixed The value from the array or the default value
     */
    protected function getArrayValue(array $data, string $path, $default = null)
    {
        $currentValue = $data;
        $keyPaths = (array)explode('.', $path);

        foreach ($keyPaths as $currentKey) {
            if (isset($currentValue->$currentKey)) {
                $currentValue = $currentValue->$currentKey;
                continue;
            }
            if (isset($currentValue[$currentKey])) {
                $currentValue = $currentValue[$currentKey];
                continue;
            }

            return $default;
        }

        return $currentValue === null ? $default : $currentValue;
    }
}
