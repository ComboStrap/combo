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
        $resourceCombo = $this->getResource();
        $resourceType = $resourceCombo->getType();
        $desc = "The $resourceType name is the shortest $resourceType description. It should be at maximum a couple of words long.";
        if ($resourceType === Page::TYPE) {
            $desc = $desc . " It's used mainly in navigational components.";
        }
        return $desc;
    }

    public function getLabel(): string
    {
        return "Name";
    }

    static public function getName(): string
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

    /**
     * @return string
     */
    public function getDefaultValue(): string
    {

        $resourceCombo = $this->getResource();

        $pathName = $resourceCombo->getPath()->getLastNameWithoutExtension();
        /**
         * If this is a home page, the default
         * is the parent path name
         */
        if ($resourceCombo->getType() == Page::TYPE) {
            if ($pathName === Site::getIndexPageName()) {
                $names = $resourceCombo->getPath()->getNames();
                $namesCount = sizeof($names);
                if ($namesCount >= 2) {
                    $pathName = $names[$namesCount - 2];
                }
            }
        }

        $words = preg_split("/\s/", preg_replace("/[-_]/", " ", $pathName));
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

    /**
     * @return string
     */
    public function getValueOrDefault(): string
    {
        try {
            return $this->getValue();
        } catch (ExceptionNotFound $e) {
            return $this->getDefaultValue();
        }

    }


}
