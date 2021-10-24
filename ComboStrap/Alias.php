<?php


namespace ComboStrap;


class Alias
{

    const ALIAS_PATH_PROPERTY = "path";
    const ALIAS_TYPE_PROPERTY = "type";

    const  REDIRECT = "redirect";
    const SYNONYM = "synonym";
    const CANONICAL = "alias";


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

    public static function toAliasArray($aliases, Page $page): array
    {
        $aliasArray = [];
        foreach ($aliases as $alias) {
            if (is_array($alias)) {
                $path = $alias[Alias::ALIAS_PATH_PROPERTY];
                $aliasArray[$path] = Alias::create($page, $path)
                    ->setType($alias[Alias::ALIAS_TYPE_PROPERTY]);
            } else {
                if (!is_string($alias)) {
                    $alias = StringUtility::toString($alias);
                    LogUtility::msg("The alias element ($alias) is not a string", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                }
                $aliasArray[$alias] = Alias::create($page, $alias);
            }
        }
        return $aliasArray;
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
        return array_map(
            function ($aliasObject) {
                return [
                    Alias::ALIAS_PATH_PROPERTY => $aliasObject->getPath(),
                    Alias::ALIAS_TYPE_PROPERTY => $aliasObject->getType()
                ];
            },
            $aliases
        );
    }

    public
    function setType(string $type): Alias
    {
        $this->type = $type;
        return $this;
    }

    public
    function __toString()
    {
        return $this->path;
    }


}
