<?php
/**
 * Copyright (c) 2020. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap\Tag;


use ComboStrap\ColorRgb;
use ComboStrap\Dimension;
use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionCompile;
use ComboStrap\ExecutionContext;
use ComboStrap\LogUtility;
use ComboStrap\MarkupPath;
use ComboStrap\MarkupRenderUtility;
use ComboStrap\PluginUtility;
use ComboStrap\SiteConfig;
use ComboStrap\Spacing;
use ComboStrap\TagAttribute\Align;
use ComboStrap\TagAttributes;
use Mpdf\Css\Border;

/**
 * Class AdsUtility
 * @package ComboStrap
 *
 * TODO: Injection: Words Between Ads (https://wpadvancedads.com/manual/minimum-amount-of-words-between-ads/)
 */
class AdTag
{

    const CONF_ADS_MIN_LOCAL_LINE_DEFAULT = 2;
    const CONF_ADS_MIN_LOCAL_LINE_KEY = 'AdsMinLocalLine';
    const CONF_ADS_LINE_BETWEEN_DEFAULT = 13;
    const CONF_ADS_LINE_BETWEEN_KEY = 'AdsLineBetween';
    const CONF_ADS_MIN_SECTION_NUMBER_DEFAULT = 2;
    const CONF_ADS_MIN_SECTION_KEY = 'AdsMinSectionNumber';


    const ADS_NAMESPACE = ':combostrap:ads:';

    /**
     * All in-article should start with this prefix
     */
    const PREFIX_IN_ARTICLE_ADS = "inarticle";

    /**
     * Do we show a placeholder when there is no ad page
     * for a in-article
     */
    const CONF_IN_ARTICLE_PLACEHOLDER = 'AdsInArticleShowPlaceholder';
    const CONF_IN_ARTICLE_PLACEHOLDER_DEFAULT = 0;
    const MARKUP = "ad";
    const NAME_ATTRIBUTE = "name";

    public static function showAds($sectionLineCount, $currentLineCountSinceLastAd, $sectionNumber, $adsCounter, $isLastSection, ?MarkupPath $markupPath): bool
    {
        $isHiddenPage = false;
        if ($markupPath !== null) {
            try {
                $isHiddenPage = isHiddenPage($markupPath->getWikiId());
            } catch (ExceptionBadArgument $e) {
                //
            }
        }
        global $ACT;
        if (
            $ACT !== ExecutionContext::ADMIN_ACTION && // Not in the admin page
            $isHiddenPage === false &&// No ads on hidden pages
            (
                (
                    $sectionLineCount > self::CONF_ADS_MIN_LOCAL_LINE_DEFAULT && // Doesn't show any ad if the section does not contains this minimum number of line
                    $currentLineCountSinceLastAd > self::CONF_ADS_LINE_BETWEEN_DEFAULT && // Every N line,
                    $sectionNumber > self::CONF_ADS_MIN_SECTION_NUMBER_DEFAULT // Doesn't show any ad before
                )
                or
                // Show always an ad after a number of section
                (
                    $adsCounter == 0 && // Still no ads
                    $sectionNumber > self::CONF_ADS_MIN_SECTION_NUMBER_DEFAULT && // Above the minimum number of section
                    $sectionLineCount > self::CONF_ADS_MIN_LOCAL_LINE_DEFAULT // Minimum line in the current section (to avoid a pub below a header)
                )
                or
                // Sometimes the last section (reference) has not so much line and it avoids to show an ads at the end
                // even if the number of line (space) was enough
                (
                    $isLastSection && // The last section
                    $currentLineCountSinceLastAd > self::CONF_ADS_LINE_BETWEEN_DEFAULT  // Every N line,
                )
            )) {
            return true;
        } else {
            return false;
        }
    }

    public static function showPlaceHolder()
    {
        return SiteConfig::getConfValue(self::CONF_IN_ARTICLE_PLACEHOLDER);
    }

    /**
     * Return the full page location
     * @param $name
     * @return string
     */
    public static function getAdPage($name): string
    {
        return strtolower(self::ADS_NAMESPACE . $name);
    }

    /**
     * Return the id of the div that wrap the ad
     * @param $name - the name of the page ad
     * @return string|string[]
     */
    public static function getTagId($name)
    {
        return str_replace(":", "-", substr(self::getAdPage($name), 1));
    }

    public static function render(TagAttributes $attributes): string
    {

        $name = $attributes->getValueAndRemoveIfPresent(self::NAME_ATTRIBUTE);
        if ($name === null) {
            return LogUtility::wrapInRedForHtml("Internal error: the name attribute is mandatory to render an ad");
        }

        $attributes->setId(AdTag::getTagId($name));

        $adsPageId = AdTag::getAdPage($name);
        if (!page_exists($adsPageId)) {

            if (AdTag::showPlaceHolder()) {


                $link = PluginUtility::getDocumentationHyperLink("automatic/in-article/ad#AdsInArticleShowPlaceholder", "In-article placeholder");
                $htmlAttributes = $attributes
                    ->setComponentAttributeValue(ColorRgb::COLOR, "dark")
                    ->setComponentAttributeValue(Spacing::SPACING_ATTRIBUTE, "m-3 p-3")
                    ->setComponentAttributeValue(Align::ALIGN_ATTRIBUTE, "center text-align")
                    ->setComponentAttributeValue(Dimension::WIDTH_KEY,"600")
                    ->setComponentAttributeValue("border-color","dark")
                    ->toHTMLAttributeString();
                return <<<EOF
<div $htmlAttributes>
Ads Page Id ($adsPageId ) not found. <br>
Showing the $link<br>
</div>
EOF;

            } else {

                return LogUtility::wrapInRedForHtml("The ad page (" . $adsPageId . ") does not exist");

            }
        }

        try {
            $content = MarkupRenderUtility::renderId2Xhtml($adsPageId);
        } catch (ExceptionCompile $e) {
            return LogUtility::wrapInRedForHtml("Error: " . $e->getMessage());
        }

        /**
         * We wrap the ad with a div to locate it and id it
         */
        $htmlAttributesString = $attributes
            ->toHTMLAttributeString();

        return <<<EOF
<div $htmlAttributesString>
$content
</div>
EOF;

    }
}
