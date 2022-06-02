<?php

namespace ComboStrap;


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
        if($fileName===false){
            throw new \ReflectionException("The class is defined in php core or in a php extension");
        }
        return LocalPath::createFromPath($fileName);
    }
}
