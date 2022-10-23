<?php

namespace Tzunghaor\SettingsBundle\Helper;

use Symfony\Component\PropertyAccess\PropertyAccess;

class ObjectHydrator
{
    /**
     * Creates an instance of $class and fills it with $values.
     * If the class has a constructor, the hydration will pass as many elements in $values to the constructor
     * as it can, it uses PropertyAccessor to pass the remaining values.
     * If the $class constructor has non-optional parameters that have no element in $values, the hydrator will
     * pass null.
     *
     * @param string $class fully qualified class name
     * @param array  $values [$name => $value, ...] not needed to have element for every attribute of $class
     *
     * @return object instance of $class
     *
     * @throws \ReflectionException
     */
    public static function hydrate(string $class, array $values)
    {
        $reflectionClass = new \ReflectionClass($class);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        // first try to pass constructor arguments
        $handledValues = [];
        if ($reflectionClass->hasMethod('__construct')) {
            $constructorArguments = [];
            foreach ($reflectionClass->getMethod('__construct')->getParameters() as $reflectionParameter) {
                $name = $reflectionParameter->getName();
                // not stopping if this parameter does not have value in $values, because the next ones might have
                if (!array_key_exists($name, $values)) {
                    $constructorArguments[] = $reflectionParameter->isOptional() ?
                        $reflectionParameter->getDefaultValue() : null;
                } else {
                    $constructorArguments[] = $values[$name];
                    $handledValues[$name] = true;
                }

            }

            $newObject = $reflectionClass->newInstanceArgs($constructorArguments);
        } else {
            $newObject = new $class();
        }

        // let's set settings that are not passed as constructor arguments
        $remainingValues = array_diff_key($values, $handledValues);

        foreach ($remainingValues as $name => $value) {
            $propertyAccessor->setValue($newObject, $name, $value);
        }

        return $newObject;
    }
}