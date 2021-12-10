<?php


namespace ComboStrap;


class Json
{

    const TYPE_OBJECT = "{";
    const PARENT_TYPE_ARRAY = "[";
    const TAB_SPACES_COUNTER = 4;
    const EXTENSION = "json";

    /**
     * @var array
     */
    private $jsonArray;

    /**
     * Json constructor.
     * @param array|null $jsonValue
     */
    public function __construct(?array $jsonValue = null)
    {
        $this->jsonArray = $jsonValue;

    }

    public static function createEmpty(): Json
    {
        return new Json([]);
    }

    public static function createFromArray(array $actual): Json
    {
        return new Json($actual);
    }

    public static function getValidationLink(string $json): string
    {
        return "See the errors it by clicking on <a href=\"https://jsonformatter.curiousconcept.com/?data=" . urlencode($json) . "\">this link</a>";
    }

    /**
     * Used to make diff
     * @return false|string
     */
    public function toNormalizedJsonString()
    {
        $jsonArray = $this->getJsonArray();
        if ($jsonArray === null) {
            /**
             * Edge case empty string
             */
            return "";
        }
        return json_encode($jsonArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * This formatting make the object on one line for a list of object
     * making the frontmatter compacter (one line, one meta)
     * @return string
     * @deprecated You should use the {@link MetadataFrontmatterStore::toFrontmatterJsonString()} instead
     */
    public function toFrontMatterFormat(): string
    {

        $jsonArray = $this->getJsonArray();
        return MetadataFrontmatterStore::toFrontmatterJsonString($jsonArray);

    }


    /**
     * @throws ExceptionCombo
     */
    public
    static function createFromString($jsonString): Json
    {
        if($jsonString===null || $jsonString === "" ){
            return new Json();
        }
        $jsonArray = json_decode($jsonString, true);
        if ($jsonArray === null) {
            throw new ExceptionCombo("The string is not a valid json. Value: ($jsonString)");
        }
        return new Json($jsonArray);
    }

    /**
     * @return mixed
     */
    public
    function toJsonObject()
    {
        $jsonString = $this->getJsonString();
        return json_decode($jsonString);

    }

    public
    function toArray(): ?array
    {
        return $this->getJsonArray();

    }

    public
    function toPrettyJsonString()
    {
        return $this->toNormalizedJsonString();
    }

    private
    function getJsonString()
    {
        return  json_encode($this->jsonArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array|null
     */
    private
    function getJsonArray(): ?array
    {
        return $this->jsonArray;
    }

    public
    function toMinifiedJsonString()
    {
        return json_encode($this->jsonArray);
    }


}
