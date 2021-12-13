<?php


namespace ComboStrap;


class ResourceName extends MetadataText
{


    public const PROPERTY_NAME = "name";

    public static function createForResource($page): ResourceName
    {
        return (new ResourceName())
            ->setResource($page);
    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
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
        return self::PROPERTY_NAME;
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

        $resourceCombo = $this->getResource();

        $pathName = $resourceCombo->getPath()->getLastNameWithoutExtension();
        switch ($resourceCombo->getResourceType()) {

            case Page::RESOURCE_TYPE:
                /**
                 * If this is a home page, the default
                 * is the parent path name
                 */
                if ($pathName === Site::getHomePageName()) {
                    $names = $resourceCombo->getPath()->getNames();
                    $namesCount = sizeof($names);
                    if ($namesCount >= 2) {
                        $pathName = $names[$namesCount - 2];
                    }
                }
                break;

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
