<?php

/**
 * Load all class via Plugin Utility
 */
require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

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
use ComboStrap\PageProtection;
use ComboStrap\PagePublicationDate;
use ComboStrap\PageType;
use ComboStrap\PageUrlType;
use ComboStrap\Prism;
use ComboStrap\RasterImageLink;
use ComboStrap\Region;
use ComboStrap\Shadow;
use ComboStrap\SvgDocument;
use ComboStrap\SvgImageLink;
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
$meta[action_plugin_combo_router::ROUTER_ENABLE_CONF] = array('onoff');
$meta['ShowPageNameIsNotUnique'] = array('onoff');
$meta['ShowMessageClassic'] = array('onoff');

$actionChoices = array('multichoice', '_choices' => array(
    action_plugin_combo_router::NOTHING,
    action_plugin_combo_router::GO_TO_BEST_END_PAGE_NAME,
    action_plugin_combo_router::GO_TO_NS_START_PAGE,
    action_plugin_combo_router::GO_TO_BEST_PAGE_NAME,
    action_plugin_combo_router::GO_TO_BEST_NAMESPACE,
    action_plugin_combo_router::GO_TO_SEARCH_ENGINE
));
$meta['GoToEditMode'] = array('onoff');
$meta['ActionReaderFirst'] = $actionChoices;
$meta['ActionReaderSecond'] = $actionChoices;
$meta['ActionReaderThird'] = $actionChoices;
$meta['WeightFactorForSamePageName'] = array('string');
$meta['WeightFactorForStartPage'] = array('string');
$meta['WeightFactorForSameNamespace'] = array('string');

$meta[UrlManagerBestEndPage::CONF_MINIMAL_SCORE_FOR_REDIRECT] = array('string');

$meta[Canonical::CONF_CANONICAL_LAST_NAMES_COUNT] = array('string');
$meta[action_plugin_combo_canonical::CONF_CANONICAL_FOR_GA_PAGE_VIEW] = array('onoff');

/**
 * Icon namespace where the downloaded icon are stored
 */
require_once(__DIR__ . '/../syntax/icon.php');
$meta[Icon::CONF_ICONS_MEDIA_NAMESPACE] = array('string');
$meta[Icon::CONF_DEFAULT_ICON_LIBRARY] = array('multichoice', '_choices' => array_keys(Icon::PUBLIC_LIBRARY_ACRONYM));


/**
 * Css optimization
 */
$meta[action_plugin_combo_css::CONF_ENABLE_MINIMAL_FRONTEND_STYLESHEET] = array('onoff');
$meta[action_plugin_combo_css::CONF_DISABLE_DOKUWIKI_STYLESHEET] = array('onoff');

/**
 * Metadata Viewer
 */
$meta[syntax_plugin_combo_metadata::CONF_METADATA_DEFAULT_ATTRIBUTES] = array('string');

/**
 * Badge
 */
$meta[syntax_plugin_combo_badge::CONF_DEFAULT_ATTRIBUTES_KEY] = array('string');

/**
 * Ads
 */
require_once(__DIR__ . '/../ComboStrap/AdsUtility.php');
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
require_once(__DIR__ . '/../ComboStrap/LowQualityPage.php');
$meta[LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE] = array('onoff');
$meta[LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_MODE] = array('multichoice', '_choices' => array(
    PageProtection::CONF_VALUE_ROBOT,
    PageProtection::CONF_VALUE_FEED,
    PageProtection::CONF_VALUE_ACL,
    PageProtection::CONF_VALUE_HIDDEN
));
$meta[LowQualityPage::CONF_LOW_QUALITY_PAGE_LINK_TYPE] = array('multichoice', '_choices' => array(
    PageProtection::PAGE_PROTECTION_LINK_NORMAL,
    PageProtection::PAGE_PROTECTION_LINK_WARNING,
    PageProtection::PAGE_PROTECTION_LINK_LOGIN,
));

/**
 * Preformatted mode enable
 */
$meta[syntax_plugin_combo_preformatted::CONF_PREFORMATTED_ENABLE] = array('onoff');
$meta[syntax_plugin_combo_preformatted::CONF_PREFORMATTED_EMPTY_CONTENT_NOT_PRINTED_ENABLE] = array('onoff');

/**
 * The mandatory rules
 */
$meta[renderer_plugin_combo_analytics::CONF_MANDATORY_QUALITY_RULES] = array('multicheckbox', '_choices' => renderer_plugin_combo_analytics::QUALITY_RULES);

/**
 * The quality rules excluded from monitoring
 */
$meta[action_plugin_combo_qualitymessage::CONF_DISABLE_QUALITY_MONITORING] = array('onoff');
$meta[action_plugin_combo_qualitymessage::CONF_EXCLUDED_QUALITY_RULES_FROM_DYNAMIC_MONITORING] = array('multicheckbox', '_choices' => renderer_plugin_combo_analytics::QUALITY_RULES);

/**
 * Link
 */
$meta[MarkupRef::CONF_USE_DOKUWIKI_CLASS_NAME] = array('onoff');
$meta[MarkupRef::CONF_PREVIEW_LINK] = array('onoff');
$meta[syntax_plugin_combo_link::CONF_DISABLE_LINK] = array('onoff');

/**
 * Twitter
 */
$meta[action_plugin_combo_metatwitter::CONF_TWITTER_SITE_HANDLE] = array('string');
$meta[action_plugin_combo_metatwitter::CONF_TWITTER_SITE_ID] = array('string');
$meta[action_plugin_combo_metatwitter::CONF_DEFAULT_TWITTER_IMAGE] = array('string');
$meta[action_plugin_combo_metatwitter::CONF_DONT_NOT_TRACK] = array('multichoice', '_choices' => array(
    action_plugin_combo_metatwitter::CONF_ON,
    action_plugin_combo_metatwitter::CONF_OFF
));
$meta[syntax_plugin_combo_blockquote::CONF_TWEET_WIDGETS_THEME] = array('string');
$meta[syntax_plugin_combo_blockquote::CONF_TWEET_WIDGETS_BORDER] = array('string');

/**
 * Page Image
 */
$meta[PageImages::CONF_DISABLE_FIRST_IMAGE_AS_PAGE_IMAGE] = array('onoff');

/**
 * Facebook
 */
$meta[action_plugin_combo_metafacebook::CONF_DEFAULT_FACEBOOK_IMAGE] = array('string');

/**
 * Language region
 */
$meta[Region::CONF_SITE_LANGUAGE_REGION] = array("string");

/**
 * Late publication protection
 */
$meta[PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_ENABLE] = array('onoff');
$meta[PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_MODE] = array('multichoice', '_choices' => array(
    PageProtection::CONF_VALUE_ROBOT,
    PageProtection::CONF_VALUE_FEED,
    PageProtection::CONF_VALUE_ACL,
    PageProtection::CONF_VALUE_HIDDEN
));

/**
 * Default Page Type
 */
$meta[PageType::CONF_DEFAULT_PAGE_TYPE] = array("string");

/**
 * Default Shadow level
 */
$meta[Shadow::CONF_DEFAULT_VALUE] = array('multichoice', '_choices' => array(
    Shadow::CONF_SMALL_LEVEL_VALUE,
    Shadow::CONF_MEDIUM_LEVEL_VALUE,
    Shadow::CONF_LARGE_LEVEL_VALUE,
    Shadow::CONF_EXTRA_LARGE_LEVEL_VALUE
));


/**
 * Big Svg Lazy load
 */
require_once(__DIR__ . '/../ComboStrap/SvgImageLink.php');
$meta[SvgImageLink::CONF_LAZY_LOAD_ENABLE] = array('onoff');

/**
 * Big Svg Injection
 */
$meta[SvgImageLink::CONF_SVG_INJECTION_ENABLE] = array('onoff');

/**
 * Svg Optimization
 */
$meta[SvgDocument::CONF_SVG_OPTIMIZATION_ENABLE] = array('onoff');

/**
 * Svg Optimization Inline
 */
$meta[SvgImageLink::CONF_MAX_KB_SIZE_FOR_INLINE_SVG] = array('string');

/**
 * Svg Upload Group Name
 */
$meta[action_plugin_combo_svg::CONF_SVG_UPLOAD_GROUP_NAME] = array('string');

/**
 * Svg The attribute that are deleted with the optimization
 * {@link SvgDocument::CONF_OPTIMIZATION_ATTRIBUTES_TO_DELETE}
 */
$meta[SvgDocument::CONF_OPTIMIZATION_ATTRIBUTES_TO_DELETE] = array('string');
$meta[SvgDocument::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE] = array('string');
$meta[SvgDocument::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE_IF_EMPTY] = array('string');
$meta[SvgDocument::CONF_OPTIMIZATION_NAMESPACES_TO_KEEP] = array('string');
$meta[SvgDocument::CONF_PRESERVE_ASPECT_RATIO_DEFAULT] = array('string');

/**
 * Raster Lazy load image
 */
$meta[RasterImageLink::CONF_LAZY_LOADING_ENABLE] = array('onoff');
$meta[RasterImageLink::CONF_RESPONSIVE_IMAGE_MARGIN] = array('string');
$meta[RasterImageLink::CONF_RETINA_SUPPORT_ENABLED] = array('onoff');

/**
 * Lazy loading
 */
$meta[LazyLoad::CONF_LAZY_LOADING_PLACEHOLDER_COLOR] = array("string");

/**
 * Internal media
 */
$meta[syntax_plugin_combo_media::CONF_IMAGE_ENABLE] = array('onoff');

/**
 * Internal media default linking
 */
$meta[MediaLink::CONF_DEFAULT_LINKING] = array('multichoice', '_choices' => array(
    MediaLink::LINKING_DIRECT_VALUE,
    MediaLink::LINKING_DETAILS_VALUE,
    MediaLink::LINKING_LINKONLY_VALUE,
    MediaLink::LINKING_NOLINK_VALUE,
));

/**
 * Default breakpoint
 */
$meta[FloatAttribute::CONF_FLOAT_DEFAULT_BREAKPOINT] = array('multichoice', '_choices' => array(
    "xs",
    "sm",
    "md",
    "lg",
    "xl",
    "xxl"
));

/**
 * Outline Numbering
 */
$meta[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_ENABLE] = array("onoff");
$meta[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL2] = array('multichoice', '_choices' => action_plugin_combo_outlinenumbering::CONF_COUNTER_STYLES_CHOICES);
$meta[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL3] = array('multichoice', '_choices' => action_plugin_combo_outlinenumbering::CONF_COUNTER_STYLES_CHOICES);
$meta[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL4] = array('multichoice', '_choices' => action_plugin_combo_outlinenumbering::CONF_COUNTER_STYLES_CHOICES);
$meta[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL5] = array('multichoice', '_choices' => action_plugin_combo_outlinenumbering::CONF_COUNTER_STYLES_CHOICES);
$meta[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL6] = array('multichoice', '_choices' => action_plugin_combo_outlinenumbering::CONF_COUNTER_STYLES_CHOICES);
$meta[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_COUNTER_SEPARATOR] = array("string");
$meta[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_PREFIX] = array("string");
$meta[action_plugin_combo_outlinenumbering::CONF_OUTLINE_NUMBERING_SUFFIX] = array("string");

/**
 * Identity form
 */
$meta[Identity::CONF_ENABLE_LOGO_ON_IDENTITY_FORMS] = array("onoff");
$meta[action_plugin_combo_registration::CONF_ENABLE_REGISTER_FORM] = array("onoff");
$meta[action_plugin_combo_login::CONF_ENABLE_LOGIN_FORM] = array("onoff");
$meta[action_plugin_combo_resend::CONF_ENABLE_RESEND_PWD_FORM] = array("onoff");
$meta[action_plugin_combo_profile::CONF_ENABLE_PROFILE_UPDATE_FORM] = array("onoff");
$meta[action_plugin_combo_profile::CONF_ENABLE_PROFILE_DELETE_FORM] = array("onoff");

/**
 * Comment
 */
$meta[syntax_plugin_combo_comment::CONF_OUTPUT_COMMENT] = array("onoff");

/**
 * Cache
 */
$meta[action_plugin_combo_staticresource::CONF_STATIC_CACHE_ENABLED] = array("onoff");

/**
 * Link Wizard
 */
$meta[action_plugin_combo_linkwizard::CONF_ENABLE_ENHANCED_LINK_WIZARD] = array("onoff");

/**
 * Canonical Url Type
 */
$meta[PageUrlType::CONF_CANONICAL_URL_TYPE] = array('multichoice', '_choices' => PageUrlType::CONF_VALUES);

/**
 * Frontmatter on sumbit
 */
$meta[syntax_plugin_combo_frontmatter::CONF_ENABLE_FRONT_MATTER_ON_SUBMIT] = array("onoff");

/**
 * Heading
 */
$meta[syntax_plugin_combo_headingwiki::CONF_WIKI_HEADING_ENABLE] = array("onoff");

/**
 * Branding Colors
 */
$meta[ColorRgb::PRIMARY_COLOR_CONF] = array("string");
$meta[ColorRgb::SECONDARY_COLOR_CONF] = array("string");
$meta[ColorRgb::BRANDING_COLOR_INHERITANCE_ENABLE_CONF] = array("onoff");

/**
 * Highlight
 */
$meta[syntax_plugin_combo_highlightwiki::CONF_HIGHLIGHT_WIKI_ENABLE] = array("onoff");
