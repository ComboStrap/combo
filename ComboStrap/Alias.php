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

    public static function getPossibleTypesValues(): array
    {
        return self::ALIAS_TYPE_VALUES;
    }

    public static function getDefaultType()
    {
        return self::REDIRECT;
    }

    /**
     * @return Page
     */
    public
    function getPage(): Page
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

    public
    function setType(string $type): Alias
    {
        if (!in_array($type, self::getPossibleTypesValues())) {
            $pageAnchor = $this->getPage()->getAnchorLink();
            LogUtility::msg("Bad Alias Type. The alias type value ($type) for the alias path ({$this->getPath()}) of the page ({$pageAnchor})");
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
