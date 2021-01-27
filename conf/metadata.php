<?php

use ComboStrap\AdsUtility;
use ComboStrap\IconUtility;
use ComboStrap\MetadataUtility;
use ComboStrap\Prism;
use ComboStrap\LowQualityPage;
use ComboStrap\UrlManagerBestEndPage;

require_once(__DIR__ . '/../syntax/related.php');


// https://www.dokuwiki.org/devel:configuration
$meta[syntax_plugin_combo_related::MAX_LINKS_CONF] = array('numeric');
$meta[syntax_plugin_combo_related::EXTRA_PATTERN_CONF] = array('string');

/**
 * Disqus
 */
require_once(__DIR__ . '/../syntax/disqus.php');
$meta[syntax_plugin_combo_disqus::CONF_DEFAULT_ATTRIBUTES] = array('string');


/**
 * Url Manager
 */
$meta['ShowPageNameIsNotUnique'] = array('onoff');
$meta['ShowMessageClassic'] = array('onoff');

require_once(__DIR__ . '/../action/urlmanager.php');
$actionChoices = array('multichoice', '_choices' => array(
    action_plugin_combo_urlmanager::NOTHING,
    action_plugin_combo_urlmanager::GO_TO_BEST_END_PAGE_NAME,
    action_plugin_combo_urlmanager::GO_TO_NS_START_PAGE,
    action_plugin_combo_urlmanager::GO_TO_BEST_PAGE_NAME,
    action_plugin_combo_urlmanager::GO_TO_BEST_NAMESPACE,
    action_plugin_combo_urlmanager::GO_TO_SEARCH_ENGINE
));
$meta['GoToEditMode'] = array('onoff');
$meta['ActionReaderFirst'] = $actionChoices;
$meta['ActionReaderSecond'] = $actionChoices;
$meta['ActionReaderThird'] = $actionChoices;
$meta['WeightFactorForSamePageName'] = array('string');
$meta['WeightFactorForStartPage'] = array('string');
$meta['WeightFactorForSameNamespace'] = array('string');
require_once(__DIR__ . '/../class/UrlManagerBestEndPage.php');
$meta[UrlManagerBestEndPage::CONF_MINIMAL_SCORE_FOR_REDIRECT] = array('string');

$meta[action_plugin_combo_metacanonical::CANONICAL_LAST_NAMES_COUNT_CONF] = array('string');

/**
 * Icon namespace where the downloaded icon are stored
 */
require_once(__DIR__ . '/../syntax/icon.php');
$meta[IconUtility::CONF_ICONS_MEDIA_NAMESPACE] = array('string');

/**
 * Css optimization
 */
$meta[action_plugin_combo_css::CONF_ENABLE_MINIMAL_FRONTEND_STYLESHEET] = array('onoff');
$meta[action_plugin_combo_css::CONF_DISABLE_DOKUWIKI_STYLESHEET] = array('onoff');

/**
 * Metadata Viewer
 */
$meta[MetadataUtility::CONF_METADATA_DEFAULT_ATTRIBUTES] = array('string');
$meta[MetadataUtility::CONF_ENABLE_WHEN_EDITING] = array('onoff');

/**
 * Badge
 */
$meta[syntax_plugin_combo_badge::CONF_DEFAULT_ATTRIBUTES_KEY] = array('string');

/**
 * Ads
 */
require_once(__DIR__ . '/../class/AdsUtility.php');
$meta[AdsUtility::CONF_IN_ARTICLE_PLACEHOLDER] = array('onoff');

/**
 * Code / File / Console
 */
$meta[syntax_plugin_combo_code::CONF_CODE_ENABLE] = array('onoff');
$meta[Prism::CONF_PRISM_THEME] = array('multichoice', '_choices' => array_keys(Prism::THEMES_INTEGRITY));
$meta[Prism::CONF_BASH_PROMPT] = array('string');
$meta[Prism::CONF_BATCH_PROMPT] = array('string');
$meta[Prism::CONF_POWERSHELL_PROMPT] = array('string');
$meta[syntax_plugin_combo_file::CONF_FILE_ENABLE] = array('onoff');

/**
 * Quality (SEO)
 */
require_once(__DIR__ . '/../class/LowQualityPage.php');
$meta[LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE] = array('onoff');
$meta[LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_MODE] = array('multichoice', '_choices' => array(
    LowQualityPage::ACL,
    LowQualityPage::HIDDEN
));

/**
 * Preformatted mode enable
 */
$meta[syntax_plugin_combo_preformatted::CONF_PREFORMATTED_ENABLE] = array('onoff');

/**
 * The mandatory rules
 */
$meta[renderer_plugin_combo_analytics::CONF_MANDATORY_QUALITY_RULES] = array('multicheckbox', '_choices' => renderer_plugin_combo_analytics::CONF_MANDATORY_QUALITY_RULES_DEFAULT_VALUE);
