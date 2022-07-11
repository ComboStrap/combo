<?php

namespace ComboStrap;


use ReflectionClass;

class ClassUtility
{

    /**
     * @param object|string $objectOrClass
     * @return LocalPath
     *
     * Example to get the path of a class, you would call:
     * ```
     * getClassPath($this)
     * ```
     * @throws \ReflectionException - if this is not possible to return the path
     */
    public static function getClassPath($objectOrClass): LocalPath
    {
        $reflector = new \ReflectionClass($objectOrClass);
        $fileName = $reflector->getFileName();
        if ($fileName === false) {
            throw new \ReflectionException("The class is defined in php core or in a php extension");
        }
        return LocalPath::createFromPath($fileName);
    }

    public static function getClassImplementingInterface(string $interface): array
    {
        $class = [];
        $getDeclaredClasses = get_declared_classes();
        foreach ($getDeclaredClasses as $className) {
            if (in_array($interface, class_implements($className))) {
                $class[] = $className;
            }
        }
        return $class;
    }

    /**
     * @throws \ReflectionException
     */
    public static function getObjectImplementingInterface(string $interface): array
    {
        $classes = self::getClassImplementingInterface($interface);
        $objects = [];
        foreach ($classes as $class) {
            $classReflection = new ReflectionClass($class);
            if (!$classReflection->isAbstract()) {
                $objects[] = new $class();
            }
        }
        return $objects;
    }

    public static function isLoaded(string $class): bool
    {
        return class_exists($class, false);
    }
}
