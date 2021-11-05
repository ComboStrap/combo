<?php


namespace ComboStrap;


class Alias
{

    const ALIAS_PATH_PROPERTY = "path";
    const ALIAS_TYPE_PROPERTY = "type";

    const  REDIRECT = "redirect";
    const SYNONYM = "synonym";
    const CANONICAL = "alias";
    const ALIAS_TYPE_VALUES = [self::SYNONYM, self::REDIRECT];


    private $path; // the path of the alias
    private $page;
    /**
     * @var string
     */
    private $type = self::REDIRECT;

    /**
     * Alias constructor.
     * @param {Page} $page
     * @param {string} $alias
     */
    public function __construct($page, $path)
    {
        $this->page = $page;
        if (empty($path)) {
            LogUtility::msg("Alias: To create an alias, the path value should not be empty", LogUtility::LVL_MSG_ERROR);
            return;
        }
        if (!is_string($path)) {
            LogUtility::msg("Alias: To create an alias, the path value should a string. Value: " . var_export($path, true), LogUtility::LVL_MSG_ERROR);
            return;
        }
        DokuPath::addRootSeparatorIfNotPresent($path);
        $this->path = $path;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param $aliases
     * @param Page $page
     * @return Alias[]|null
     */
    public static function toAliasArray($aliases, Page $page): ?array
    {
        if ($aliases === null) return null;

        $aliasArray = [];
        foreach ($aliases as $key => $value) {
            if (is_array($value)) {
                if(empty($key)){
                    LogUtility::msg("The key of the frontmatter alias should not be empty as it's the alias path");
                    continue;
                }
                $path = $key;
                $type = $value[Alias::ALIAS_TYPE_PROPERTY];
                $aliasArray[$path] = Alias::create($page, $path)
                    ->setType($type);
            } else {
                $path = $value;
                if(empty($path)){
                    LogUtility::msg("The value of the frontmatter alias array should not be empty as it's the alias path");
                    continue;
                }
                if (!is_string($path)) {
                    $path = StringUtility::toString($path);
                    LogUtility::msg("The alias element ($path) is not a string", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                }
                $aliasArray[$path] = Alias::create($page, $path);
            }
        }
        return $aliasArray;
    }

    public static function getPossibleTypesValues(): array
    {
        return self::ALIAS_TYPE_VALUES;
    }

    public static function getDefaultType()
    {
        return self::REDIRECT;
    }

    /**
     * @return mixed
     */
    public
    function getPage()
    {
        return $this->page;
    }

    /**
     * @return string
     */
    public
    function getType(): string
    {
        return $this->type;
    }


    public
    static function create(Page $page, $alias): Alias
    {
        return new Alias($page, $alias);
    }

    /**
     * @param Alias[] $aliases
     * @return array - the array to be saved in a text/json file
     */
    public
    static function toMetadataArray(array $aliases): array
    {
        $array = [];
        foreach ($aliases as $alias) {
            $array[$alias->getPath()] = [
                Alias::ALIAS_TYPE_PROPERTY => $alias->getType()
            ];
        }
        return $array;
    }

    public
    function setType(string $type): Alias
    {
        if(!in_array($type,self::getPossibleTypesValues())){
            LogUtility::msg("The alias type ($type) is unknown");
            return $this;
        }
        $this->type = $type;
        return $this;
    }

    public
    function __toString()
    {
        return $this->path;
    }


}
