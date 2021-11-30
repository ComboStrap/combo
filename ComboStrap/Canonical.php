<?php


namespace ComboStrap;


class Canonical extends MetadataWikiPath
{

    public const CANONICAL_NAME = "canonical";

    public static function createFromPage(Page $page): Canonical
    {
        return new Canonical($page);
    }

    public function getTab(): string
    {
        return \action_plugin_combo_metamanager::TAB_REDIRECTION_VALUE;
    }

    public function getDescription(): string
    {
        return "The canonical path is a short unique path for the page (used in named permalink)";
    }

    public function getLabel(): string
    {
        return "Canonical Path";
    }

    public function getName(): string
    {
        return self::CANONICAL_NAME;
    }

    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
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
        $canonicalLastNamesCount = PluginUtility::getConfValue(\action_plugin_combo_canonical::CONF_CANONICAL_LAST_NAMES_COUNT);
        if (empty($this->getCanonical()) && $canonicalLastNamesCount > 0) {
            /**
             * Takes the last names part
             */
            $namesOriginal = $this->getPage()->getDokuNames();
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
             * If this is a start page, delete the name
             * ie javascript:start will become javascript
             */
            if ($this->getPage()->isHomePage()) {
                $names = array_slice($names, 0, $namesLength - 1);
            }
            return implode(":", $names);
        }
        return null;
    }

    public function getCanonical(): string
    {
        return self::CANONICAL_NAME;
    }


}
