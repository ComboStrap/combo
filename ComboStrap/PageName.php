<?php


namespace ComboStrap;


class PageName extends MetadataText
{


    public const NAME_PROPERTY = "name";

    public static function createFromPage($page): PageName
    {
        return new PageName($page);
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_PAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "The page name is the shortest page description. It should be at maximum a couple of words long. It's used mainly in navigational components.";
    }

    public function getLabel(): string
    {
        return "Name";
    }

    public function getName(): string
    {
        return self::NAME_PROPERTY;
    }

    public function getPersistenceType()
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

    public function getDefaultValue(): string
    {
        $pathName = $this->getPage()->getDokuPathLastName();
        /**
         * If this is a home page, the default
         * is the parent path name
         */
        if ($pathName === Site::getHomePageName()) {
            $names = $this->getPage()->getDokuNames();
            $namesCount = sizeof($names);
            if ($namesCount >= 2) {
                $pathName = $names[$namesCount - 2];
            }
        }
        $words = preg_split("/\s/", preg_replace("/-|_/", " ", $pathName));
        $wordsUc = [];
        foreach ($words as $word) {
            $wordsUc[] = ucfirst($word);
        }
        return implode(" ", $wordsUc);
    }
}
