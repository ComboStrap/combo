<?php


namespace ComboStrap;


use action_plugin_combo_metaprocessing;
use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataText;
use ComboStrap\Meta\Api\MetadataWikiPath;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;


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

    public static function createForPage(MarkupPath $page): Canonical
    {
        return (new Canonical())
            ->setResource($page);

    }

    public static function getTab(): string
    {
        return MetaManagerForm::TAB_REDIRECTION_VALUE;
    }

    public static function getDescription(): string
    {
        return "The canonical path is a short unique path for the page (used in named permalink)";
    }

    public static function getLabel(): string
    {
        return "Canonical Path";
    }

    public static function getName(): string
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
     * @return WikiPath
     * @throws ExceptionNotFound
     */
    public function getDefaultValue(): WikiPath
    {

        $resourceCombo = $this->getResource();
        if (!($resourceCombo instanceof MarkupPath)) {
            throw new ExceptionNotFound("No default value for other resources than page");
        }

        /**
         * The last part of the id as canonical
         */
        // How many last parts are taken into account in the canonical processing (2 by default)
        $canonicalLastNamesCount = SiteConfig::getConfValue(self::CONF_CANONICAL_LAST_NAMES_COUNT, 0);
        if ($canonicalLastNamesCount <= 0) {
            throw new ExceptionNotFound("Default canonical value is not enabled, no default canonical");
        }

        /**
         * Takes the last names part
         */
        $namesOriginal = $this->getResource()->getPathObject()->getNamesWithoutExtension();
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
         *
         * We don't use the {@link MarkupPath::isIndexPage()}
         * because the path `ns:ns` is also an index if the
         * page `ns:start` does not exists
         */
        if ($resourceCombo->getPathObject()->getLastNameWithoutExtension() === Site::getIndexPageName()) {
            $names = array_slice($names, 0, $namesLength - 1);
        }
        $calculatedCanonical = implode(":", $names);
        WikiPath::addRootSeparatorIfNotPresent($calculatedCanonical);
        try {
            return WikiPath::createMarkupPathFromPath($calculatedCanonical);
        } catch (ExceptionBadArgument $e) {
            LogUtility::internalError("A canonical should not be the root, should not happen", self::CANONICAL);
            throw new ExceptionNotFound();
        }

    }

    public static function getCanonical(): string
    {
        return self::CANONICAL;
    }


    public static function getDrive(): string
    {
        return WikiPath::MARKUP_DRIVE;
    }

    public static function isOnForm(): bool
    {
        return true;
    }
}
