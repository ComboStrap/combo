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
    const LATE_PUBLICATION_CLASS_NAME = "late-publication";


    public static function getLatePublicationProtectionMode()
    {

        if (PluginUtility::getConfValue(PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_ENABLE, true)) {
            return PluginUtility::getConfValue(PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_MODE);
        } else {
            return false;
        }

    }

    public static function isLatePublicationProtectionEnabled()
    {
        return PluginUtility::getConfValue(PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_ENABLE, true);
    }

    public static function createFromPage(Page $page)
    {
        return (new PagePublicationDate())
            ->setResource($page);
    }


    public function getTab(): string
    {
        return MetaManagerForm::TAB_TYPE_VALUE;
    }

    public function getDescription(): string
    {
        return "The publication date";
    }

    public function getLabel(): string
    {
        return "Publication Date";
    }

    static public function getName(): string
    {
        return self::PROPERTY_NAME;
    }


    public function buildFromStoreValue($value): Metadata
    {
        $store = $this->getReadStore();
        if (!($store instanceof MetadataDokuWikiStore)) {
            return parent::buildFromStoreValue($value);
        }

        if ($value === null) {
            /**
             * Old metadata key
             */
            $value = $store->getFromPersistentName(PagePublicationDate::OLD_META_KEY);
        }

        try {
            $this->dateTimeValue = $this->fromPersistentDateTimeUtility($value);
        } catch (ExceptionCombo $e) {
            LogUtility::msg($e->getMessage(), LogUtility::LVL_MSG_ERROR, $e->getCanonical());
        }

        return $this;
    }


    public function getPersistenceType(): string
    {
        return Metadata::PERSISTENT_METADATA;
    }

    public function getMutable(): bool
    {
        return true;
    }

    public function getDefaultValue(): ?\DateTime
    {
        return PageCreationDate::create()
            ->setResource($this->getResource())
            ->getValueOrDefault();
    }

    static public function getOldPersistentNames(): array
    {
        return [PagePublicationDate::OLD_META_KEY];
    }

    public function getCanonical(): string
    {
        return "published";
    }


}
