<?php


namespace ComboStrap\Meta\Field;



use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\ResourceCombo;
use ComboStrap\WikiPath;

class Alias
{

    const CANONICAL = "alias";


    private WikiPath $path; // the path of the alias
    private MarkupPath $page;
    /**
     * @var string
     */
    private string $type = AliasType::REDIRECT;

    /**
     * Alias constructor.
     * @param MarkupPath $page
     * @param WikiPath $path
     */
    public function __construct(MarkupPath $page, WikiPath $path)
    {
        $this->page = $page;

        $this->path = $path;
    }

    /**
     * @return WikiPath
     */
    public function getPath(): WikiPath
    {
        return $this->path;
    }



    /**
     * @return MarkupPath
     */
    public
    function getPage(): MarkupPath
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
    static function create(ResourceCombo $page, WikiPath $alias): Alias
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
        return "Alias: ($this->page) to ($this->path)";
    }


}
