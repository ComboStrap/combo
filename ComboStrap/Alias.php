<?php


namespace ComboStrap;



class Alias
{

    const CANONICAL = "alias";


    private $path; // the path of the alias
    private $page;
    /**
     * @var string
     */
    private $type = AliasType::REDIRECT;

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
    static function create(ResourceCombo $page, $alias): Alias
    {
        return new Alias($page, $alias);
    }

    public
    function setType(string $type): Alias
    {
        if (!in_array($type, AliasType::ALIAS_TYPE_VALUES)) {
            $pageAnchor = $this->getPage()->getHtmlAnchorLink();
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
