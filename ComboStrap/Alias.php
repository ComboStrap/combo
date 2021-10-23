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
        return array_map(
            function ($element) use ($page) {
                if (is_array($element)) {
                    return Alias::create($page, $element[Alias::ALIAS_PATH_PROPERTY])
                        ->setType($element[Alias::ALIAS_TYPE_PROPERTY]);
                } else {
                    if (!is_string($element)) {
                        $element = StringUtility::toString($element);
                        LogUtility::msg("The alias element ($element) is not a string", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
                    }
                    return Alias::create($page, $element);
                }
            },
            $aliases
        );
    }

    /**
     * @return mixed
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }


    public static function create(Page $page, $alias): Alias
    {
        return new Alias($page, $alias);
    }

    /**
     * @param Alias[] $aliases
     * @return array
     */
    public static function toNativeArray(array $aliases): array
    {
        return array_map(
            function ($aliasObject) {
                return [
                    ALIAS_PROPERTY => $aliasObject->getPath(),
                    ALIAS_TYPE_PROPERTY => $aliasObject->getType()
                ];
            },
            $aliases
        );
    }

    public function setType(string $type): Alias
    {
        $this->type = $type;
        return $this;
    }

    public function __toString()
    {
        return $this->path;
    }


}
