<?php


namespace ComboStrap;


class PageName extends MetadataText
{


    public const NAME_PROPERTY = "name";

    public static function createForPage($page): PageName
    {
        return (new PageName())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_PAGE_VALUE;
    }

    public function getDescription(): string
    {
        return "The name is the shortest description. It should be at maximum a couple of words long. It's used mainly in navigational components.";
    }

    public function getLabel(): string
    {
        return "Name";
    }

    public function getName(): string
    {
        return self::NAME_PROPERTY;
    }

    public function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

    public function getDefaultValue(): string
    {

        $pathName = $this->getResource()->getPath()->getLastName();

        /**
         * If this is a home page, the default
         * is the parent path name
         */
        if ($pathName === Site::getHomePageName()) {
            $names = $this->getResource()->getPath()->getNames();
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

    public function getCanonical(): string
    {
        return $this->getName();
    }


}
