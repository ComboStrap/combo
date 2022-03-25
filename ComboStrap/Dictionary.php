<?php


namespace ComboStrap;


class Dictionary
{

    /**
     * @throws ExceptionCompile
     */
    public static function getFrom(string $name): array
    {
        $path = Site::getComboDictionaryDirectory()->resolve("$name.json");
        if (!FileSystems::exists($path)) {
            throw new ExceptionCompile("The dictionary file ($path) does not exist");
        }
        $jsonContent = FileSystems::getContent($path);
        try {
            $dict = Json::createFromString($jsonContent)->toArray();
        } catch (ExceptionCompile $e) {
            throw new ExceptionCompile("The dictionary ($path) is not a valid json. Error: {$e->getMessage()}");
        }
        if ($dict === null) {
            throw new ExceptionCompile("The returned dictionary of the file ($path) is empty");
        }
        return $dict;
    }
}
