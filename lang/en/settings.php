<?php

use ComboStrap\AdsUtility;
use ComboStrap\IconUtility;
use ComboStrap\LazyLoad;
use ComboStrap\RasterImageLink;
use ComboStrap\LinkUtility;
use ComboStrap\MetadataUtility;
use ComboStrap\Page;
use ComboStrap\PageProtection;
use ComboStrap\PluginUtility;
use ComboStrap\Prism;
use ComboStrap\LowQualityPage;
use ComboStrap\Publication;
use ComboStrap\Shadow;
use ComboStrap\Site;
use ComboStrap\SvgFile;
use ComboStrap\SvgImageLink;
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
$lang[action_plugin_combo_css::CONF_DISABLE_DOKUWIKI_STYLESHEET] = PluginUtility::getUrl("css:optimization", "Css Optimization") . ' - If checked, the DokuWiki Stylesheet will not be loaded for a public user';

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
$lang[renderer_plugin_combo_analytics::CONF_MANDATORY_QUALITY_RULES] = PluginUtility::getUrl("low_quality_page", "Mandatory Quality rules") . ' - The mandatory quality rules are the rules that should pass to consider the quality of a page as not `low`';
$lang[LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE] = PluginUtility::getUrl("lqpp", "Low quality page protection") . " - If enabled, a low quality page will no more be discoverable by search engine or anonymous user.";
$lang[PageProtection::CONF_PAGE_PROTECTION_MODE] = PluginUtility::getUrl("page:protection", "Page protection mode") . " - Choose the protection mode for low quality page and late publication. Hidden (but still accessible) vs Acl (User should log in)";

/**
 * Autofrontmatter
 */
$lang[action_plugin_combo_autofrontmatter::CONF_AUTOFRONTMATTER_ENABLE] = PluginUtility::getUrl("frontmatter", "Frontmatter") . " - If enabled, a new page will be created with a frontmatter)";

/**
 * Excluded rules
 */
$lang[action_plugin_combo_qualitymessage::CONF_EXCLUDED_QUALITY_RULES_FROM_DYNAMIC_MONITORING] = PluginUtility::getUrl("quality:dynamic_monitoring", "Quality Dynamic Monitoring") . " - If chosen, the quality rules will not be monitored.)";
$lang[action_plugin_combo_qualitymessage::CONF_DISABLE_QUALITY_MONITORING] = PluginUtility::getUrl("quality:dynamic_monitoring", "Quality Dynamic Monitoring") . " - Disable the Quality Dynamic Monitoring feature (the quality message will not appear anymore)";
/**
 * Dokuwiki Class Name
 */
$lang[LinkUtility::CONF_USE_DOKUWIKI_CLASS_NAME] = PluginUtility::getUrl(syntax_plugin_combo_link::TAG, "Link") . " - Use the DokuWiki class type for links (Bootstrap conflict if enabled)";
$lang[syntax_plugin_combo_link::CONF_DISABLE_LINK] = PluginUtility::getUrl(syntax_plugin_combo_link::TAG, "Link") . " - Disable the ComboStrap link component";

/**
 * Twitter
 */
$lang[action_plugin_combo_metatwitter::CONF_DEFAULT_TWITTER_IMAGE] = PluginUtility::getUrl("twitter", "Twitter") . " - The media id (path) to the logo shown in a twitter card";
$lang[action_plugin_combo_metatwitter::CONF_TWITTER_SITE_HANDLE] = PluginUtility::getUrl("twitter", "Twitter") . " - Your twitter handle name used in a twitter card";
$lang[action_plugin_combo_metatwitter::CONF_TWITTER_SITE_ID] = PluginUtility::getUrl("twitter", "Twitter") . " - Your twitter handle id used in a twitter card";
$lang[action_plugin_combo_metatwitter::CONF_DONT_NOT_TRACK] = PluginUtility::getUrl("tweet", "Tweet") . " - Set the `do not track` attribute";
$lang[syntax_plugin_combo_blockquote::CONF_TWEET_WIDGETS_THEME] = PluginUtility::getUrl("tweet", "Tweet") . " - Set the theme for embedded twitter widget";
$lang[syntax_plugin_combo_blockquote::CONF_TWEET_WIDGETS_BORDER] = PluginUtility::getUrl("tweet", "Tweet") . " - Set the border-color for embedded twitter widget";

/**
 * Page Image
 */
$lang[Page::CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE] = PluginUtility::getUrl("page:image", "Page Image") . " - Disable the use of the first image as a page image";

/**
 * Default
 */
$lang[action_plugin_combo_metafacebook::CONF_DEFAULT_FACEBOOK_IMAGE] = PluginUtility::getUrl("facebook", "Facebook") . " - The default facebook page image (minimum size 200x200)";

/**
 * Country
 */
$lang[Site::CONF_SITE_ISO_COUNTRY] = PluginUtility::getUrl("country", "Country") . " - The default ISO country code for a page";

/**
 * Late publication
 */
$lang[Publication::CONF_LATE_PUBLICATION_PROTECTION_ENABLE] = PluginUtility::getUrl("published", "Late Publication") . " - Page with a published date in the future will be protected from search engine and the public";

/**
 * Default page type
 */
$lang[Page::CONF_DEFAULT_PAGE_TYPE] = PluginUtility::getUrl("type", "The default page type for all pages (expected the home page)");

/**
 * Default Shadow level
 */
$lang[Shadow::CONF_DEFAULT_VALUE] = PluginUtility::getUrl(Shadow::CANONICAL, "The default level applied to a shadow attributes");



/**
 * Svg
 */
$lang[SvgImageLink::CONF_LAZY_LOAD_ENABLE] = PluginUtility::getUrl(SvgImageLink::CANONICAL, "Load a svg only when they become visible");
$lang[SvgImageLink::CONF_MAX_KB_SIZE_FOR_INLINE_SVG] = PluginUtility::getUrl(SvgImageLink::CANONICAL, "The maximum size in Kb of the SVG to be included as markup in the web page");
$lang[action_plugin_combo_svg::CONF_SVG_UPLOAD_GROUP_NAME] = PluginUtility::getUrl(SvgImageLink::CANONICAL, "The name of the group of users that can upload SVG");
$lang[SvgFile::CONF_SVG_OPTIMIZATION_ENABLE] = PluginUtility::getUrl(SvgImageLink::CANONICAL, "Svg Optimization - Reduce the size of the SVG by deleting non important meta");
$lang[SvgFile::CONF_OPTIMIZATION_NAMESPACES_TO_KEEP] = PluginUtility::getUrl(SvgImageLink::CANONICAL, "Svg Optimization - The namespace prefix to keep");
$lang[SvgFile::CONF_OPTIMIZATION_ATTRIBUTES_TO_DELETE] = PluginUtility::getUrl(SvgImageLink::CANONICAL, "Svg Optimization - The attribute deleted during optimization");
$lang[SvgFile::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE_IF_EMPTY] = PluginUtility::getUrl(SvgImageLink::CANONICAL, "Svg Optimization - The element deleted if empty");
$lang[SvgFile::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE] = PluginUtility::getUrl(SvgImageLink::CANONICAL, "Svg Optimization - The element always deleted");
$lang[SvgFile::CONF_PRESERVE_ASPECT_RATIO_DEFAULT] = PluginUtility::getUrl(SvgImageLink::CANONICAL, "Svg - Default value for the preserveAspectRatio attribute");


/**
 * Lazy load image
 */
$lang[RasterImageLink::CONF_LAZY_LOADING_ENABLE] = PluginUtility::getUrl(RasterImageLink::CANONICAL, "Load the raster image only when they become visible");
$lang[RasterImageLink::CONF_RESPONSIVE_IMAGE_DPI_CORRECTION] = PluginUtility::getUrl(RasterImageLink::CANONICAL, "Responsive image sizing: If unchecked, the size DPI correction will not be applied");
$lang[RasterImageLink::CONF_RESPONSIVE_IMAGE_MARGIN] = PluginUtility::getUrl(RasterImageLink::CANONICAL, "Responsive image sizing: The image margin applied to screen size");

/**
 * Lazy loading
 */
$lang[LazyLoad::CONF_LAZY_LOADING_PLACEHOLDER_COLOR] = PluginUtility::getUrl(LazyLoad::CANONICAL, "The placeholder background color");
?>
