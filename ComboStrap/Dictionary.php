<?php


namespace ComboStrap;


class Dictionary
{

    /**
     * @throws ExceptionCombo
     */
    public static function getFrom(string $name): array
    {
        $path = LocalPath::createFromPath(Resources::getDictionaryDirectory() . "/$name.json");
        if (!FileSystems::exists($path)) {
            throw new ExceptionCombo("The dictionary file ($path) does not exist");
        }
        $jsonContent = FileSystems::getContent($path);
        $dict = Json::createFromString($jsonContent)->toArray();
        if ($dict === null) {
            throw new ExceptionCombo("The returned dictionary of the file ($path) is empty");
        }
        return $dict;
    }
}
