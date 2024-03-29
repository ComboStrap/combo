<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


use ComboStrap\Meta\Api\Metadata;
use ComboStrap\Meta\Api\MetadataDateTime;
use ComboStrap\Meta\Store\MetadataDokuWikiStore;
use DateTime;

/**
 *
 * Publication Date
 * @package ComboStrap
 *
 */
class PagePublicationDate extends MetadataDateTime
{

    /**
     * The key that contains the published date
     */
    const PROPERTY_NAME = "date_published";
    const OLD_META_KEY = "published";

    /**
     * Late publication protection
     */
    const LATE_PUBLICATION_PROTECTION_ACRONYM = "lpp";
    const CONF_LATE_PUBLICATION_PROTECTION_MODE = "latePublicationProtectionMode";
    const CONF_LATE_PUBLICATION_PROTECTION_ENABLE = "latePublicationProtectionEnable";
    const LATE_PUBLICATION_CLASS_PREFIX_NAME = "late-publication";


    public static function getLatePublicationProtectionMode()
    {

        if (SiteConfig::getConfValue(PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_ENABLE, true)) {
            return SiteConfig::getConfValue(PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_MODE);
        } else {
            return false;
        }

    }

    public static function isLatePublicationProtectionEnabled()
    {
        return SiteConfig::getConfValue(PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_ENABLE, true);
    }

    public static function createFromPage(MarkupPath $page)
    {
        return (new PagePublicationDate())
            ->setResource($page);
    }


    static public function getTab(): string
    {
        return MetaManagerForm::TAB_TYPE_VALUE;
    }

    static public function getDescription(): string
    {
        return "The publication date";
    }

    static public function getLabel(): string
    {
        return "Publication Date";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    public function setFromStoreValueWithoutException($value): Metadata
    {
        $store = $this->getReadStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return parent::setFromStoreValueWithoutException($value);
        }

        if ($value === null) {
            /**
             * Old metadata key
             */
            $value = $store->getFromName(PagePublicationDate::OLD_META_KEY);
        }

        try {
            $this->dateTimeValue = $this->fromPersistentDateTimeUtility($value);
        } catch (ExceptionCompile $e) {
            LogUtility::msg($e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
        }

        return $this;
    }


    static public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    static public function isMutable(): bool
    {
        return true;
    }

    public function getDefaultValue(): DateTime
    {
        $lastName = $this->getResource()->getPathObject()->getLastNameWithoutExtension();
        $result = preg_match("/(\d{4}-\d{2}-\d{2}).*/i", $lastName, $matches);
        if ($result === 1) {
            $date = $matches[1];
            try {
                return Iso8601Date::createFromString($date)->getDateTime();
            } catch (ExceptionBadSyntax $e) {
                // should not happen
                LogUtility::error("Internal Error: the date format is not valid. Error: {$e->getMessage()}", self::CANONICAL);
            }
        }

        return CreationDate::create()
            ->setResource($this->getResource())
            ->getValueOrDefault();
    }

    static public function getOldPersistentNames(): array
    {
        return [PagePublicationDate::OLD_META_KEY];
    }

    static public function getCanonical(): string
    {
        return "published";
    }


    static public function isOnForm(): bool
    {
        return true;
    }
}
