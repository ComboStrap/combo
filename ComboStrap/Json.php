<?php


namespace ComboStrap;


class Json
{
    private $jsonString;

    /**
     * Json constructor.
     */
    public function __construct($jsonString)
    {
        $this->jsonString = $jsonString;
    }

    public static function createEmpty(): Json
    {
        return new Json("");
    }

    public static function createFromArray(array $actual): Json
    {
        return new Json(json_encode($actual));
    }

    public static function getValidationLink(string $json)
    {
        return "See the errors it by clicking on <a href=\"https://jsonformatter.curiousconcept.com/?data=" . urlencode($json) . "\">this link</a>";
    }

    public function normalized()
    {
        return json_encode(json_decode($this->jsonString), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public static function createFromString($jsonString): Json
    {
        return new Json($jsonString);
    }

    /**
     * @return mixed
     */
    public function toJsonObject()
    {

        return json_decode($this->jsonString);

    }

    public function toArray()
    {

        return json_decode($this->jsonString, true);

    }

    public function toString()
    {
        return $this->normalized();
    }


}
