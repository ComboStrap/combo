<?php


namespace ComboStrap;


class ResourceName extends MetadataText
{


    public const PROPERTY_NAME = "name";

    public static function createForResource(ResourceCombo $resource): ResourceName
    {
        return (new ResourceName())
            ->setResource($resource);
    }

    /**
     * Return a name from a path
     * @param DokuPath $path
     * @return string
     */
    public static function getFromPath(DokuPath $path): string
    {
        try {
            $name = $path->getLastNameWithoutExtension();
        } catch (ExceptionNotFound $e) {
            try {
                $name = $path->getUrl()->getHost();
            } catch (ExceptionNotFound $e) {
                return "Unknown";
            }
        }
        $words = preg_split("/\s/", preg_replace("/[-_]/", " ", $name));
        $wordsUc = [];
        foreach ($words as $word) {
            $wordsUc[] = ucfirst($word);
        }
        return implode(" ", $wordsUc);

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

        /**
         * If this is a home page, the default
         * is the parent path name
         */
        $path = $resourceCombo->getPath();
        if ($resourceCombo instanceof Page) {
            if ($resourceCombo->isIndexPage()) {
                try {
                    $path = $resourceCombo->getParentPage()->getPath();
                } catch (ExceptionNotFound $e) {
                    // no parent page
                }
            }
        }

        return self::getFromPath($path);


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
