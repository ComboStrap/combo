<?php


namespace ComboStrap;


class Dictionary
{

    /**
     * @throws ExceptionCombo
     */
    public static function getFrom(string $name): array
    {
        $path = Site::getComboDictionaryDirectory()->resolve("$name.json");
        if (!FileSystems::exists($path)) {
            throw new ExceptionCombo("The dictionary file ($path) does not exist");
        }
        $jsonContent = FileSystems::getContent($path);
        try {
            $dict = Json::createFromString($jsonContent)->toArray();
        } catch (ExceptionCombo $e) {
            throw new ExceptionCombo("The dictionary ($path) is not a valid json. Error: {$e->getMessage()}");
        }
        if ($dict === null) {
            throw new ExceptionCombo("The returned dictionary of the file ($path) is empty");
        }
        return $dict;
    }
}
