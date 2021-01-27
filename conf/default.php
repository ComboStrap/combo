<?php
/**
 * The config manager is parsing this fucking file because they want
 * to be able to use 60*60*24 ???? :(
 *
 * See {@link \dokuwiki\plugin\config\core\ConfigParser::parse()}
 *
 * Which means that only value can be given as:
 *   * key
 *   * and value
 * The test test_plugin_default in plugin.test.php is checking that
 *
 * What fuck up is fucked up.
 *
 * The solution:
 *   * The literal value is copied
 *   * A link to the constant is placed before
 */


use ComboStrap\IconUtility;
use ComboStrap\MetadataUtility;
use ComboStrap\Prism;


/**
 * Related UI components
 */
$conf['maxLinks'] = 10;
$conf['extra_pattern'] = '{{backlinks>.}}';

/**
 * Disqus
 * See {@link syntax_plugin_combo_disqus::CONF_DEFAULT_ATTRIBUTES}
 */
$conf['disqusDefaultAttributes'] = 'shortName=""';

/**
 * ie {@link action_plugin_combo_urlmanager::GO_TO_BEST_END_PAGE_NAME}
 */
$conf['ActionReaderFirst'] = 'GoToBestEndPageName';

/**
 * ie {@link action_plugin_combo_urlmanager::GO_TO_BEST_PAGE_NAME}
 */
$conf['ActionReaderSecond'] = 'GoToBestPageName';
/**
 * ie {@link action_plugin_combo_urlmanager::GO_TO_SEARCH_ENGINE}
 */
$conf['ActionReaderThird'] = 'GoToSearchEngine';
$conf['GoToEditMode'] = 1;
$conf['ShowPageNameIsNotUnique'] = 1;
$conf['ShowMessageClassic'] = 1;
$conf['WeightFactorForSamePageName'] = 4;
$conf['WeightFactorForStartPage'] = 3;
$conf['WeightFactorForSameNamespace'] = 5;

/**
 * See {@link UrlManagerBestEndPage::CONF_MINIMAL_SCORE_FOR_REDIRECT_DEFAULT}
 */
$conf['BestEndPageMinimalScoreForIdRedirect'] = 0;

/**
 * Does automatic canonical processing is on
 */
$conf['MinimalNamesCountForAutomaticCanonical'] = 0;

/**
 * Icon Namespace
 * See {@link IconUtility::CONF_ICONS_MEDIA_NAMESPACE}
 * See {@link IconUtility::CONF_ICONS_MEDIA_NAMESPACE_DEFAULT}
 */
$conf['icons_namespace'] = ":combostrap:icons";

/**
 * Css Optimization
 * See {@link action_plugin_combo_css::CONF_ENABLE_MINIMAL_FRONTEND_STYLESHEET}
 * See {@link action_plugin_combo_css::CONF_DISABLE_DOKUWIKI_STYLESHEET}
 */
$conf['enableMinimalFrontEndStylesheet'] = 0;
$conf['disableDokuwikiStylesheet'] = 0;

/**
 * Metadata Viewer
 * See {@link \ComboStrap\MetadataUtility::CONF_ENABLE_WHEN_EDITING
 * See {@link \ComboStrap\MetadataUtility::CONF_METADATA_DEFAULT_ATTRIBUTES
 * See {@link \ComboStrap\MetadataUtility::EXCLUDE_ATTRIBUTE
 */
$conf['enableMetadataViewerWhenEditing'] = 1;
$conf['metadataViewerDefaultAttributes'] = 'title="Metadata" exclude="tableofcontents"';

/**
 * Badge
 * See {@link syntax_plugin_combo_badge::CONF_DEFAULT_ATTRIBUTES_KEY
 */
$conf['defaultBadgeAttributes'] = 'type="info" rounded="true"';

/**
 * Ads
 * See {@link \ComboStrap\AdsUtility::CONF_IN_ARTICLE_PLACEHOLDER
 */
$conf['AdsInArticleShowPlaceholder'] = 0;

/**
 * Code
 * See {@link syntax_plugin_combo_code::CONF_CODE_ENABLE}
 * {@link Prism::CONF_PRISM_THEME}
 */
$conf['codeEnable'] = 1;
$conf['fileEnable'] = 1;
$conf['prismTheme'] = "tomorrow";
$conf['bashPrompt'] = "#";
$conf['batchPrompt'] = 'C:\\';
$conf['powershellPrompt'] = 'PS C:\\';

/**
 * SEO module
 * See {@link \ComboStrap\LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE}
 */
$conf['lowQualityPageProtectionEnable'] = 0;
$conf['lowQualityPageProtectionMode'] = "acl";

/**
 * Preformatted mode disable
 * See {@link syntax_plugin_combo_preformatted::CONF_PREFORMATTED_ENABLE}
 */
$conf['preformattedEnable'] = 0;

/**
 * {@link renderer_plugin_combo_analytics::CONF_MANDATORY_QUALITY_RULES}
 */
$conf['mandatoryQualityRules'] = 'words_min,internal_backlinks_min,internal_links_min';

