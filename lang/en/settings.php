<?php

use ComboStrap\AdsUtility;
use ComboStrap\IconUtility;
use ComboStrap\MetadataUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Prism;
use ComboStrap\LowQualityPage;
use ComboStrap\UrlManagerBestEndPage;

require_once(__DIR__ . '/../../class/PluginUtility.php');
require_once(__DIR__ . '/../../class/UrlManagerBestEndPage.php');
require_once(__DIR__ . '/../../class/MetadataUtility.php');

/**
 * @var array
 */
$lang[syntax_plugin_combo_related::MAX_LINKS_CONF] = PluginUtility::getUrl("related", "Related Component") . ' - The maximum of related links shown';
$lang[syntax_plugin_combo_related::EXTRA_PATTERN_CONF] = PluginUtility::getUrl("related", "Related Component") . ' - Another pattern';

/**
 * Disqus
 */
$lang[syntax_plugin_combo_disqus::CONF_DEFAULT_ATTRIBUTES] = PluginUtility::getUrl("disqus", "Disqus") . ' - The disqus forum short name (ie the disqus website identifier)';


/**
 * Url Manager
 */
$lang['ActionReaderFirst'] = PluginUtility::getUrl("redirection:action", action_plugin_combo_urlmanager::NAME . " - Redirection Actions") . ' - First redirection action for a reader';
$lang['ActionReaderSecond'] = PluginUtility::getUrl("redirection:action", action_plugin_combo_urlmanager::NAME . " - Redirection Actions") . ' - Second redirection action for a reader if the first action don\'t success.';
$lang['ActionReaderThird'] = PluginUtility::getUrl("redirection:action", action_plugin_combo_urlmanager::NAME . " - Redirection Actions") . ' - Third redirection action for a reader if the second action don\'t success.';
$lang['GoToEditMode'] = PluginUtility::getUrl("redirection:action", action_plugin_combo_urlmanager::NAME . " - Redirection Actions") . ' - Switch directly in the edit mode for a writer ?';

$lang['ShowPageNameIsNotUnique'] = PluginUtility::getUrl("redirection:message", action_plugin_combo_urlmanager::NAME . " - Redirection Message") . ' - When redirected to the edit mode, show a message when the page name is not unique';
$lang['ShowMessageClassic'] = PluginUtility::getUrl("redirection:message", action_plugin_combo_urlmanager::NAME . " - Redirection Message") . ' - Show classic message when a action is performed ?';
$lang['WeightFactorForSamePageName'] = PluginUtility::getUrl("best:page:name", action_plugin_combo_urlmanager::NAME . " - Best Page Name") . ' - Weight factor for same page name to calculate the score for the best page.';
$lang['WeightFactorForStartPage'] = PluginUtility::getUrl("best:page:name", action_plugin_combo_urlmanager::NAME . " - Best Page Name") . ' - Weight factor for same start page to calculate the score for the best page.';
$lang['WeightFactorForSameNamespace'] = PluginUtility::getUrl("best:page:name", action_plugin_combo_urlmanager::NAME . " - Best Page Name") . ' - Weight factor for same namespace to calculate the score for the best page.';


$lang[action_plugin_combo_metacanonical::CANONICAL_LAST_NAMES_COUNT_CONF] = PluginUtility::getUrl("automatic:canonical", action_plugin_combo_urlmanager::NAME . ' - Automatic Canonical') . ' - The number of last part of a DokuWiki Id to create a ' . PluginUtility::getUrl("canonical", "canonical") . ' (0 to disable)';

$lang[UrlManagerBestEndPage::CONF_MINIMAL_SCORE_FOR_REDIRECT] = PluginUtility::getUrl("best:end:page:name", action_plugin_combo_urlmanager::NAME . ' - Best End Page Name') . ' - The number of last part of a DokuWiki Id to perform a ' . PluginUtility::getUrl("id:redirect", "ID redirect") . ' (0 to disable)';


$lang[IconUtility::CONF_ICONS_MEDIA_NAMESPACE] = PluginUtility::getUrl("icon#configuration", "UI Icon Component") . ' - The media namespace where the downloaded icons will be searched and saved';

/**
 * Css Optimization
 */
$lang[action_plugin_combo_css::CONF_ENABLE_MINIMAL_FRONTEND_STYLESHEET] = PluginUtility::getUrl("css:optimization", "Css Optimization") . ' - If enabled, the DokuWiki Stylesheet for a public user will be minimized';
$lang[action_plugin_combo_css::CONF_DISABLE_DOKUWIKI_STYLESHEET] = PluginUtility::getUrl("css:optimization", "Css Optimization") . ' - If disabled, the DokuWiki Stylesheet will not be loaded for a public user';

/**
 * Metdataviewer
 */
$lang[MetadataUtility::CONF_METADATA_DEFAULT_ATTRIBUTES] = PluginUtility::getUrl("metadata:viewer", "Metadata Viewer") . ' - The default attributes of the metadata component';
$lang[MetadataUtility::CONF_ENABLE_WHEN_EDITING] = PluginUtility::getUrl("metadata:viewer", "Metadata Viewer") . ' - Shows the metadata box when editing a page';

/**
 * Badge
 */
$lang[syntax_plugin_combo_badge::CONF_DEFAULT_ATTRIBUTES_KEY] = PluginUtility::getUrl("badge", "Badge") . ' - Defines the default badge attributes';

/**
 * Ads
 */
$lang[AdsUtility::CONF_IN_ARTICLE_PLACEHOLDER] = PluginUtility::getUrl("automatic:in-article:ad", "Automatic In-article Ad") . ' - Show a placeholder if the in-article ad page was not found';

/**
 * Code enabled
 */
$lang[Prism::CONF_PRISM_THEME] = PluginUtility::getUrl("prism", "Prism Component") . ' - The prism theme used for syntax highlighting in the code/file/console component';
$lang[Prism::CONF_BATCH_PROMPT] = PluginUtility::getUrl("prism", "Prism Component") . ' - The default prompt for the batch language';
$lang[Prism::CONF_BASH_PROMPT] = PluginUtility::getUrl("prism", "Prism Component") . ' - The default prompt for the bash language';
$lang[Prism::CONF_POWERSHELL_PROMPT] = PluginUtility::getUrl("prism", "Prism Component") . ' - The default prompt for the powershell language';
$lang[syntax_plugin_combo_code::CONF_CODE_ENABLE] = PluginUtility::getUrl("code", "Code Component") . ' - Enable or disable the code component';
$lang[syntax_plugin_combo_file::CONF_FILE_ENABLE] = PluginUtility::getUrl("file", "File Component") . ' - Enable or disable the file component';



/**
 * Preformatted mode
 */
$lang[syntax_plugin_combo_preformatted::CONF_PREFORMATTED_ENABLE] = PluginUtility::getUrl("preformatted", "Preformatted Component") . ' - If checked, the default preformatted mode of dokuwiki is enabled';

/**
 * Mandatory rules
 */
$lang[renderer_plugin_combo_analytics::CONF_MANDATORY_QUALITY_RULES] = PluginUtility::getUrl("quality:rule", "Mandatory Quality rules") . ' - The mandatory quality rules are the rules that should pass to consider the quality of page as not `low`';
$lang[LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE] = PluginUtility::getUrl("lqpp", "Low quality page protection")." - If enabled, a low quality page will no more be discoverable by search engine or anonymous user.";
$lang[LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_MODE] = PluginUtility::getUrl("lqpp", "Low quality page protection mode")." - Choose the protection mode. Hidden (but still accessible) vs Acl (User should log in)";


?>
