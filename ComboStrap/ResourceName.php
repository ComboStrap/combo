<?php


namespace ComboStrap;


use action_plugin_combo_metaprocessing;
use ComboStrap\Meta\Api\MetadataText;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;

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
     * @param Path $path
     * @return string
     */
    public static function getFromPath(Path $path): string
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

    public static function getTab(): string
    {
        return MetaManagerForm::TAB_PAGE_VALUE;
    }

    public static function getDescription(): string
    {

        return "A name is the shortest description. It should be at maximum a couple of words long. It's used in navigational components or as a default in link.";

    }

    public static function getLabel(): string
    {
        return "The name of a page";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }

    public static function getPersistenceType(): string
    {
        return MetadataDokuWikiStore::PERSISTENT_DOKUWIKI_KEY;
    }

    public static function isMutable(): bool
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
        $path = $resourceCombo->getPathObject();
        if ($resourceCombo instanceof MarkupPath) {


            if ($resourceCombo->isIndexPage() && !$resourceCombo->isRootHomePage()) {

                try {
                    $path = $path->getParent();
                } catch (ExceptionNotFound $e) {
                    // no parent path
                    // should not happen because even the home page (:start) has
                    // a parent (ie :)
                    return Site::getIndexPageName();
                }
            }
        }

        return self::getFromPath($path);


    }

    public static function getCanonical(): string
    {
        return static::getName();
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

    public static function isOnForm(): bool
    {
        return true;
    }
}
