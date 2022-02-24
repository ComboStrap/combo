<?php

require_once(__DIR__ . '/../../ComboStrap/PluginUtility.php');

use ComboStrap\AdsUtility;
use ComboStrap\Canonical;
use ComboStrap\ColorRgb;
use ComboStrap\FloatAttribute;
use ComboStrap\Icon;
use ComboStrap\Identity;
use ComboStrap\LazyLoad;
use ComboStrap\MarkupRef;
use ComboStrap\LowQualityPage;
use ComboStrap\MediaLink;
use ComboStrap\PageImages;
use ComboStrap\PagePublicationDate;
use ComboStrap\PageType;
use ComboStrap\PageUrlType;
use ComboStrap\PluginUtility;
use ComboStrap\Prism;
use ComboStrap\RasterImageLink;
use ComboStrap\Region;
use ComboStrap\Shadow;
use ComboStrap\SvgDocument;
use ComboStrap\SvgImageLink;
use ComboStrap\UrlManagerBestEndPage;


/**
 * @var array
 */
$lang[syntax_plugin_combo_related::MAX_LINKS_CONF] = PluginUtility::getDocumentationHyperLink("related", "Related Component") . ' - The maximum of related links shown';
$lang[syntax_plugin_combo_related::EXTRA_PATTERN_CONF] = PluginUtility::getDocumentationHyperLink("related", "Related Component") . ' - Another pattern';

/**
 * Disqus
 */
$lang[syntax_plugin_combo_disqus::CONF_DEFAULT_ATTRIBUTES] = PluginUtility::getDocumentationHyperLink("disqus", "Disqus") . ' - The disqus forum short name (ie the disqus website identifier)';


/**
 * Url Manager
 */
$lang[action_plugin_combo_router::ROUTER_ENABLE_CONF] = PluginUtility::getDocumentationHyperLink(action_plugin_combo_router::CANONICAL, action_plugin_combo_router::NAME ) . ' - If unchecked, the URL manager will be disabled';
$lang['ActionReaderFirst'] = PluginUtility::getDocumentationHyperLink("redirection:action", action_plugin_combo_router::NAME . " - Redirection Actions") . ' - First redirection action for a reader';
$lang['ActionReaderSecond'] = PluginUtility::getDocumentationHyperLink("redirection:action", action_plugin_combo_router::NAME . " - Redirection Actions") . ' - Second redirection action for a reader if the first action don\'t success.';
$lang['ActionReaderThird'] = PluginUtility::getDocumentationHyperLink("redirection:action", action_plugin_combo_router::NAME . " - Redirection Actions") . ' - Third redirection action for a reader if the second action don\'t success.';
$lang['GoToEditMode'] = PluginUtility::getDocumentationHyperLink("redirection:action", action_plugin_combo_router::NAME . " - Redirection Actions") . ' - Switch directly in the edit mode for a writer ?';

$lang['ShowPageNameIsNotUnique'] = PluginUtility::getDocumentationHyperLink("redirection:message", action_plugin_combo_router::NAME . " - Redirection Message") . ' - When redirected to the edit mode, show a message when the page name is not unique';
$lang['ShowMessageClassic'] = PluginUtility::getDocumentationHyperLink("redirection:message", action_plugin_combo_router::NAME . " - Redirection Message") . ' - Show classic message when a action is performed ?';
$lang['WeightFactorForSamePageName'] = PluginUtility::getDocumentationHyperLink("best:page:name", action_plugin_combo_router::NAME . " - Best Page Name") . ' - Weight factor for same page name to calculate the score for the best page.';
$lang['WeightFactorForStartPage'] = PluginUtility::getDocumentationHyperLink("best:page:name", action_plugin_combo_router::NAME . " - Best Page Name") . ' - Weight factor for same start page to calculate the score for the best page.';
$lang['WeightFactorForSameNamespace'] = PluginUtility::getDocumentationHyperLink("best:page:name", action_plugin_combo_router::NAME . " - Best Page Name") . ' - Weight factor for same namespace to calculate the score for the best page.';


$lang[Canonical::CONF_CANONICAL_LAST_NAMES_COUNT] = PluginUtility::getDocumentationHyperLink("automatic:canonical", 'Automatic Canonical') . ' - The number of last part of a page path to create a ' . PluginUtility::getDocumentationHyperLink("canonical", "canonical") . ' (0 to disable)';
$lang[action_plugin_combo_canonical::CONF_CANONICAL_FOR_GA_PAGE_VIEW] = PluginUtility::getDocumentationHyperLink(action_plugin_combo_canonical::CANONICAL, 'Canonical') . ' - If enable and if set, the canonical will be reported to the Google Analytics Plugin as page path';

$lang[UrlManagerBestEndPage::CONF_MINIMAL_SCORE_FOR_REDIRECT] = PluginUtility::getDocumentationHyperLink("best:end:page:name", action_plugin_combo_router::NAME . ' - Best End Page Name') . ' - The number of last part of a DokuWiki Id to perform a ' . PluginUtility::getDocumentationHyperLink(action_plugin_combo_router::PERMANENT_REDIRECT_CANONICAL, "permanent redirect") . ' (0 to disable)';


/**
 * Icon
 */
$lang[Icon::CONF_ICONS_MEDIA_NAMESPACE] = PluginUtility::getDocumentationHyperLink("icon#configuration", "UI Icon Component") . ' - The media namespace where the downloaded icons will be searched and saved';
$lang[Icon::CONF_DEFAULT_ICON_LIBRARY] = PluginUtility::getDocumentationHyperLink("icon#configuration", "UI Icon Component") . ' - The default icon library from where the icon is downloaded if not specified';

/**
 * Front end Optimization
 */
$lang[action_plugin_combo_css::CONF_ENABLE_MINIMAL_FRONTEND_STYLESHEET] = PluginUtility::getDocumentationHyperLink("frontend:optimization", "Frontend Optimization") . ' - If enabled, the DokuWiki Stylesheet for a public user will be minimized';
$lang[action_plugin_combo_css::CONF_DISABLE_DOKUWIKI_STYLESHEET] = PluginUtility::getDocumentationHyperLink("frontend:optimization", "Frontend Optimization") . ' - If checked, the DokuWiki Stylesheet will not be loaded for a public user';

/**
 * Metdataviewer
 */
$lang[syntax_plugin_combo_metadata::CONF_METADATA_DEFAULT_ATTRIBUTES] = PluginUtility::getDocumentationHyperLink("metadata:viewer", "Metadata Viewer") . ' - The default attributes of the metadata component';

/**
 * Badge
 */
$lang[syntax_plugin_combo_badge::CONF_DEFAULT_ATTRIBUTES_KEY] = PluginUtility::getDocumentationHyperLink("badge", "Badge") . ' - Defines the default badge attributes';

/**
 * Ads
 */
$lang[AdsUtility::CONF_IN_ARTICLE_PLACEHOLDER] = PluginUtility::getDocumentationHyperLink("automatic:in-article:ad", "Automatic In-article Ad") . ' - Show a placeholder if the in-article ad page was not found';

/**
 * Code enabled
 */
$lang[Prism::CONF_PRISM_THEME] = PluginUtility::getDocumentationHyperLink("prism", "Prism Component") . ' - The prism theme used for syntax highlighting in the code/file/console component';
$lang[Prism::CONF_BATCH_PROMPT] = PluginUtility::getDocumentationHyperLink("prism", "Prism Component") . ' - The default prompt for the batch language';
$lang[Prism::CONF_BASH_PROMPT] = PluginUtility::getDocumentationHyperLink("prism", "Prism Component") . ' - The default prompt for the bash language';
$lang[Prism::CONF_POWERSHELL_PROMPT] = PluginUtility::getDocumentationHyperLink("prism", "Prism Component") . ' - The default prompt for the powershell language';
$lang[syntax_plugin_combo_code::CONF_CODE_ENABLE] = PluginUtility::getDocumentationHyperLink("code", "Code Component") . ' - Enable or disable the code component';
$lang[syntax_plugin_combo_file::CONF_FILE_ENABLE] = PluginUtility::getDocumentationHyperLink("file", "File Component") . ' - Enable or disable the file component';


/**
 * Preformatted mode
 */
$lang[syntax_plugin_combo_preformatted::CONF_PREFORMATTED_ENABLE] = PluginUtility::getDocumentationHyperLink("preformatted", "Preformatted Component") . ' - If checked, the default preformatted mode of dokuwiki is enabled';
$lang[syntax_plugin_combo_preformatted::CONF_PREFORMATTED_EMPTY_CONTENT_NOT_PRINTED_ENABLE] = PluginUtility::getDocumentationHyperLink("preformatted", "Preformatted Component") . ' - If unchecked, a blank line with only two spaces will be printed as an empty block of code';

/**
 * Mandatory rules
 */
$lang[renderer_plugin_combo_analytics::CONF_MANDATORY_QUALITY_RULES] = PluginUtility::getDocumentationHyperLink(LowQualityPage::LOW_QUALITY_PAGE_CANONICAL, "Mandatory Quality rules") . ' - The mandatory quality rules are the rules that should pass to consider the quality of a page as not `low`';
$lang[LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE] = PluginUtility::getDocumentationHyperLink(LowQualityPage::LQPP_CANONICAL, "Low quality page protection") . " - If enabled, a low quality page will no more be discoverable by search engine or anonymous user.";
$lang[LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_MODE] = PluginUtility::getDocumentationHyperLink(LowQualityPage::LQPP_CANONICAL, "Low quality page protection") . " - Choose the protection mode for low quality page.";
$lang[LowQualityPage::CONF_LOW_QUALITY_PAGE_LINK_TYPE] = PluginUtility::getDocumentationHyperLink(LowQualityPage::LQPP_CANONICAL, "Low quality page protection") . " - Choose the link created to a low quality page.";


/**
 * Excluded rules
 */
$lang[action_plugin_combo_qualitymessage::CONF_EXCLUDED_QUALITY_RULES_FROM_DYNAMIC_MONITORING] = PluginUtility::getDocumentationHyperLink("quality:dynamic_monitoring", "Quality Dynamic Monitoring") . " - If chosen, the quality rules will not be monitored.)";
$lang[action_plugin_combo_qualitymessage::CONF_DISABLE_QUALITY_MONITORING] = PluginUtility::getDocumentationHyperLink("quality:dynamic_monitoring", "Quality Dynamic Monitoring") . " - Disable the Quality Dynamic Monitoring feature (the quality message will not appear anymore)";

/**
 * Link
 */
$lang[syntax_plugin_combo_link::CONF_DISABLE_LINK] = PluginUtility::getDocumentationHyperLink(syntax_plugin_combo_link::TAG, "Link") . " - Disable the ComboStrap link component";
$lang[MarkupRef::CONF_USE_DOKUWIKI_CLASS_NAME] = PluginUtility::getDocumentationHyperLink(syntax_plugin_combo_link::TAG, "Link") . " - Use the DokuWiki class type for links (Bootstrap conflict if enabled)";
$lang[MarkupRef::CONF_PREVIEW_LINK] = PluginUtility::getDocumentationHyperLink(syntax_plugin_combo_link::TAG, "Link") . " - Add a page preview on all internal links when a user is hovering";

/**
 * Twitter
 */
$lang[action_plugin_combo_metatwitter::CONF_DEFAULT_TWITTER_IMAGE] = PluginUtility::getDocumentationHyperLink("twitter", "Twitter") . " - The media id (path) to the logo shown in a twitter card";
$lang[action_plugin_combo_metatwitter::CONF_TWITTER_SITE_HANDLE] = PluginUtility::getDocumentationHyperLink("twitter", "Twitter") . " - Your twitter handle name used in a twitter card";
$lang[action_plugin_combo_metatwitter::CONF_TWITTER_SITE_ID] = PluginUtility::getDocumentationHyperLink("twitter", "Twitter") . " - Your twitter handle id used in a twitter card";
$lang[action_plugin_combo_metatwitter::CONF_DONT_NOT_TRACK] = PluginUtility::getDocumentationHyperLink("tweet", "Tweet") . " - Set the `do not track` attribute";
$lang[syntax_plugin_combo_blockquote::CONF_TWEET_WIDGETS_THEME] = PluginUtility::getDocumentationHyperLink("tweet", "Tweet") . " - Set the theme for embedded twitter widget";
$lang[syntax_plugin_combo_blockquote::CONF_TWEET_WIDGETS_BORDER] = PluginUtility::getDocumentationHyperLink("tweet", "Tweet") . " - Set the border-color for embedded twitter widget";

/**
 * Page Image
 */
$lang[PageImages::CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE] = PluginUtility::getDocumentationHyperLink(syntax_plugin_combo_frontmatter::METADATA_IMAGE_CANONICAL, "Metadata Image") . " - Disable the use of the first image as a page image";

/**
 * Default
 */
$lang[action_plugin_combo_metafacebook::CONF_DEFAULT_FACEBOOK_IMAGE] = PluginUtility::getDocumentationHyperLink("facebook", "Facebook") . " - The default facebook page image (minimum size 200x200)";

/**
 * Country
 */
$lang[Region::CONF_SITE_LANGUAGE_REGION] = PluginUtility::getDocumentationHyperLink("region", "Language Region") . " - The default region language.";

/**
 * Late publication
 */
$lang[PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_ENABLE] = PluginUtility::getDocumentationHyperLink(PagePublicationDate::LATE_PUBLICATION_PROTECTION_ACRONYM, "Late Publication") . " - Page with a published date in the future will be protected from search engine and the public";
$lang[PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_MODE] = PluginUtility::getDocumentationHyperLink(PagePublicationDate::LATE_PUBLICATION_PROTECTION_ACRONYM, "Late Publication") . " - The mode of protection for a late published page";

/**
 * Default page type
 */
$lang[PageType::CONF_DEFAULT_PAGE_TYPE] = PluginUtility::getDocumentationHyperLink("type", "The default page type for all pages (expected the home page)");

/**
 * Default Shadow level
 */
$lang[Shadow::CONF_DEFAULT_VALUE] = PluginUtility::getDocumentationHyperLink(Shadow::CANONICAL, "Shadow - The default level applied to a shadow attributes");


/**
 * Svg
 */
$lang[SvgImageLink::CONF_LAZY_LOAD_ENABLE] = PluginUtility::getDocumentationHyperLink(SvgImageLink::CANONICAL, "Svg - Load a svg only when they become visible");
$lang[SvgImageLink::CONF_MAX_KB_SIZE_FOR_INLINE_SVG] = PluginUtility::getDocumentationHyperLink(SvgImageLink::CANONICAL, "Svg - The maximum size in Kb of the SVG to be included as markup in the web page");
$lang[SvgImageLink::CONF_SVG_INJECTION_ENABLE] = PluginUtility::getDocumentationHyperLink(SvgImageLink::CANONICAL, "Svg Injection - Replace the image as svg in the HTML when downloaded to be add styling capabilities");
$lang[action_plugin_combo_svg::CONF_SVG_UPLOAD_GROUP_NAME] = PluginUtility::getDocumentationHyperLink(SvgImageLink::CANONICAL, "Svg Security - The name of the group of users that can upload SVG");
$lang[SvgDocument::CONF_SVG_OPTIMIZATION_ENABLE] = PluginUtility::getDocumentationHyperLink(SvgImageLink::CANONICAL, "Svg Optimization - Reduce the size of the SVG by deleting non important meta");
$lang[SvgDocument::CONF_OPTIMIZATION_NAMESPACES_TO_KEEP] = PluginUtility::getDocumentationHyperLink(SvgImageLink::CANONICAL, "Svg Optimization - The namespace prefix to keep");
$lang[SvgDocument::CONF_OPTIMIZATION_ATTRIBUTES_TO_DELETE] = PluginUtility::getDocumentationHyperLink(SvgImageLink::CANONICAL, "Svg Optimization - The attribute deleted during optimization");
$lang[SvgDocument::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE_IF_EMPTY] = PluginUtility::getDocumentationHyperLink(SvgImageLink::CANONICAL, "Svg Optimization - The element deleted if empty");
$lang[SvgDocument::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE] = PluginUtility::getDocumentationHyperLink(SvgImageLink::CANONICAL, "Svg Optimization - The element always deleted");
$lang[SvgDocument::CONF_PRESERVE_ASPECT_RATIO_DEFAULT] = PluginUtility::getDocumentationHyperLink(SvgImageLink::CANONICAL, "Svg - Default value for the preserveAspectRatio attribute");


/**
 * Lazy load image
 */
$lang[RasterImageLink::CONF_LAZY_LOADING_ENABLE] = PluginUtility::getDocumentationHyperLink(RasterImageLink::CANONICAL, "Raster Image - Load the raster image only when they become visible");
$lang[RasterImageLink::CONF_RETINA_SUPPORT_ENABLED] = PluginUtility::getDocumentationHyperLink(RasterImageLink::CANONICAL, "Raster Image - Retina Support: If checked, the images downloaded will match the display capabilities (the size DPI correction will not be applied)");
$lang[RasterImageLink::CONF_RESPONSIVE_IMAGE_MARGIN] = PluginUtility::getDocumentationHyperLink(RasterImageLink::CANONICAL, "Raster Image - Responsive image sizing: The image margin applied to screen size");

/**
 * Lazy loading
 */
$lang[LazyLoad::CONF_LAZY_LOADING_PLACEHOLDER_COLOR] = PluginUtility::getDocumentationHyperLink(LazyLoad::CANONICAL, "Lazy Loading - The placeholder background color");

/**
 * Image
 */
$lang[syntax_plugin_combo_media::CONF_IMAGE_ENABLE] = PluginUtility::getDocumentationHyperLink(MediaLink::CANONICAL, "Image - If unchecked, the image component will be disabled");
$lang[MediaLink::CONF_DEFAULT_LINKING] = PluginUtility::getDocumentationHyperLink(MediaLink::CANONICAL, "Image - The default link option from an internal image.");

/**
 * Float
 */
$lang[FloatAttribute::CONF_FLOAT_DEFAULT_BREAKPOINT] = PluginUtility::getDocumentationHyperLink(FloatAttribute::CANONICAL, "Float - The default breakpoint that applies to floated value (left, right, none)");

/**
 * Outline
 */
$lang[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_ENABLE] = PluginUtility::getDocumentationHyperLink(action_plugin_combo_outlinenumbering::CANONICAL, "Outline - if checked, outline numbering will be applied");
$lang[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL2] = PluginUtility::getDocumentationHyperLink(action_plugin_combo_outlinenumbering::CANONICAL, "Outline - The counter style for the level 2");
$lang[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL3] = PluginUtility::getDocumentationHyperLink(action_plugin_combo_outlinenumbering::CANONICAL, "Outline - The counter style for the level 3");
$lang[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL4] = PluginUtility::getDocumentationHyperLink(action_plugin_combo_outlinenumbering::CANONICAL, "Outline - The counter style for the level 4");
$lang[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL5] = PluginUtility::getDocumentationHyperLink(action_plugin_combo_outlinenumbering::CANONICAL, "Outline - The counter style for the level 5");
$lang[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL6] = PluginUtility::getDocumentationHyperLink(action_plugin_combo_outlinenumbering::CANONICAL, "Outline - The counter style for the level 6");
$lang[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_COUNTER_SEPARATOR] = PluginUtility::getDocumentationHyperLink(action_plugin_combo_outlinenumbering::CANONICAL, "Outline - The separator between counters");
$lang[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_PREFIX] = PluginUtility::getDocumentationHyperLink(action_plugin_combo_outlinenumbering::CANONICAL, "Outline - The prefix of the outline numbering");
$lang[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_SUFFIX] = PluginUtility::getDocumentationHyperLink(action_plugin_combo_outlinenumbering::CANONICAL, "Outline - The suffix of the outline numbering");


/**
 * Identity
 */
$lang[Identity::CONF_ENABLE_LOGO_ON_IDENTITY_FORMS] = PluginUtility::getDocumentationHyperLink(Identity::CANONICAL, "If checked, the logo is shown on the identity forms (login, register, resend)");
$lang[action_plugin_combo_login::CONF_ENABLE_LOGIN_FORM] = PluginUtility::getDocumentationHyperLink(Identity::CANONICAL, "If checked, the login form will be styled by Combo");
$lang[action_plugin_combo_registration::CONF_ENABLE_REGISTER_FORM] = PluginUtility::getDocumentationHyperLink(Identity::CANONICAL, "If enable, the register form will be styled by Combo");
$lang[action_plugin_combo_resend::CONF_ENABLE_RESEND_PWD_FORM] = PluginUtility::getDocumentationHyperLink(Identity::CANONICAL, "If enable, the resend form will be styled by Combo");
$lang[action_plugin_combo_profile::CONF_ENABLE_PROFILE_UPDATE_FORM] = PluginUtility::getDocumentationHyperLink(Identity::CANONICAL, "If enable, the profile update form will be styled by Combo");
$lang[action_plugin_combo_profile::CONF_ENABLE_PROFILE_DELETE_FORM] = PluginUtility::getDocumentationHyperLink(Identity::CANONICAL, "If enable, the profile delete form will be styled by Combo");

/**
 * Comment
 */
$lang[syntax_plugin_combo_comment::CONF_OUTPUT_COMMENT] = PluginUtility::getDocumentationHyperLink(syntax_plugin_combo_comment::CANONICAL, "If enable, the comments will be present in the created page as comment (ie not visible but present)");

/**
 * Smart cache
 */
$lang[action_plugin_combo_staticresource::CONF_STATIC_CACHE_ENABLED] = PluginUtility::getDocumentationHyperLink(action_plugin_combo_cache::CANONICAL, "If disabled, the smart image browser cache will be disabled");

/**
 * Link Wizard
 */
$lang[action_plugin_combo_linkwizard::CONF_ENABLE_ENHANCED_LINK_WIZARD] = PluginUtility::getDocumentationHyperLink(syntax_plugin_combo_link::TAG, "If unchecked, the link wizard will not search for the term in the path, title, heading and name of the pages");

/**
 * Url Type
 */
$lang[PageUrlType::CONF_CANONICAL_URL_TYPE] = PluginUtility::getDocumentationHyperLink("page:url", "The type of url used for a page.");

/**
 * Frontmatter
 */
$lang[syntax_plugin_combo_frontmatter::CONF_ENABLE_FRONT_MATTER_ON_SUBMIT] = PluginUtility::getDocumentationHyperLink(syntax_plugin_combo_frontmatter::CANONICAL, "If checked, the metadata manager will create a frontmatter on submit.");

/**
 * Heading
 */
$lang[syntax_plugin_combo_headingwiki::CONF_WIKI_HEADING_ENABLE]= PluginUtility::getDocumentationHyperLink(syntax_plugin_combo_heading::CANONICAL, "If unchecked, the combo wiki heading is disabled (You cannot add extra formatting syntax)");

/**
 * Colors
 */
$lang[ColorRgb::PRIMARY_COLOR_CONF]= PluginUtility::getDocumentationHyperLink(ColorRgb::BRANDING_COLOR_CANONICAL, "Set the primary branding color");
$lang[ColorRgb::SECONDARY_COLOR_CONF]= PluginUtility::getDocumentationHyperLink(ColorRgb::BRANDING_COLOR_CANONICAL, "Set the secondary branding color");
$lang[ColorRgb::BRANDING_COLOR_INHERITANCE_ENABLE_CONF ]= PluginUtility::getDocumentationHyperLink(ColorRgb::BRANDING_COLOR_CANONICAL, "Enable or disable the branding colors inheritance");

/**
 * Highlight
 */
$lang[syntax_plugin_combo_highlightwiki::CONF_HIGHLIGHT_WIKI_ENABLE]= PluginUtility::getDocumentationHyperLink(syntax_plugin_combo_highlightwiki::CANONICAL, "Enable or disable the wiki highlight component");
?>
