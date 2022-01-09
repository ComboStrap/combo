<?php


namespace ComboStrap;


class Canonical extends MetadataWikiPath
{

    public const PROPERTY_NAME = "canonical";
    public const CANONICAL = "canonical";

    /**
     * The auto-canonical feature does not create any canonical value on the file system
     * but creates a canonical in the database (where the {@link \action_plugin_combo_router}
     * takes its information and it enables to route via a calculated canonical
     * (ie the {@link Canonical::getDefaultValue()}
     */
    public const CONF_CANONICAL_LAST_NAMES_COUNT = 'MinimalNamesCountForAutomaticCanonical';

    public static function createForPage(Page $page): Canonical
    {
        return (new Canonical())
            ->setResource($page);

    }

    public function getTab(): string
    {
        return MetaManagerForm::TAB_REDIRECTION_VALUE;
    }

    public function getDescription(): string
    {
        return "The canonical path is a short unique path for the page (used in named permalink)";
    }

    public function getLabel(): string
    {
        return "Canonical Path";
    }

    public static function getName(): string
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

    public function getDefaultValue(): ?string
    {
        /**
         * The last part of the id as canonical
         */
        // How many last parts are taken into account in the canonical processing (2 by default)
        $canonicalLastNamesCount = PluginUtility::getConfValue(self::CONF_CANONICAL_LAST_NAMES_COUNT, 0);
        if ($canonicalLastNamesCount > 0) {
            /**
             * Takes the last names part
             */
            $namesOriginal = $this->getResource()->getPath()->getNames();
            /**
             * Delete the identical names at the end
             * To resolve this problem
             * The page (viz:viz) and the page (data:viz:viz) have the same canonical.
             * The page (viz:viz) will get the canonical viz
             * The page (data:viz) will get the canonical  data:viz
             */
            $i = sizeof($namesOriginal) - 1;
            $names = $namesOriginal;
            while ($namesOriginal[$i] == $namesOriginal[$i - 1]) {
                unset($names[$i]);
                $i--;
                if ($i <= 0) {
                    break;
                }
            }
            /**
             * Minimal length check
             */
            $namesLength = sizeof($names);
            if ($namesLength > $canonicalLastNamesCount) {
                $names = array_slice($names, $namesLength - $canonicalLastNamesCount);
            }
            /**
             * If this is a `start` page, delete the name
             * ie javascript:start will become javascript
             * (Not a home page)
             */
            if ($this->getResource()->isStartPage()) {
                $names = array_slice($names, 0, $namesLength - 1);
            }
            $calculatedCanonical = implode(":", $names);
            DokuPath::addRootSeparatorIfNotPresent($calculatedCanonical);
            return $calculatedCanonical;
        }
        return null;
    }

    public function getCanonical(): string
    {
        return self::CANONICAL;
    }


}
