<?php
/**
 *
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
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


use ComboStrap\Canonical;
use ComboStrap\ContainerTag;
use ComboStrap\IconDownloader;
use ComboStrap\Identity;
use ComboStrap\Meta\Field\Region;
use ComboStrap\MetadataFrontmatterStore;
use ComboStrap\Outline;
use ComboStrap\PageType;
use ComboStrap\PageUrlType;
use ComboStrap\Prism;
use ComboStrap\Router;
use ComboStrap\SiteConfig;
use ComboStrap\Snippet;
use ComboStrap\Tag\RelatedTag;


/**
 * Related UI components
 * {@link RelatedTag::MAX_LINKS_CONF}
 * {@link RelatedTag::MAX_LINKS_CONF_DEFAULT}
 */
$conf['maxLinks'] = 10;
$conf['extra_pattern'] = '{{backlinks>.}}';

/**
 * Disqus
 * See {@link syntax_plugin_combo_disqus::CONF_DEFAULT_ATTRIBUTES}
 */
$conf['disqusDefaultAttributes'] = 'shortName=""';

/**
 * Enable ie {@link action_plugin_combo_router::ROUTER_ENABLE_CONF}
 */
$conf['enableRouter'] = 1;
/**
 * ie {@link Router::GO_TO_BEST_END_PAGE_NAME}
 */
$conf['ActionReaderFirst'] = 'GoToBestEndPageName';

/**
 * ie {@link Router::GO_TO_BEST_PAGE_NAME}
 */
$conf['ActionReaderSecond'] = 'GoToBestPageName';
/**
 * ie {@link Router::GO_TO_SEARCH_ENGINE}
 */
$conf['ActionReaderThird'] = 'GoToSearchEngine';
$conf['GoToEditMode'] = 1;
/**
 * ie {@link action_plugin_combo_routermessage::CONF_SHOW_PAGE_NAME_IS_NOT_UNIQUE}
 * ie {@link action_plugin_combo_routermessage::CONF_SHOW_MESSAGE_CLASSIC}
 */
$conf['ShowPageNameIsNotUnique'] = 1;
$conf['ShowMessageClassic'] = 1;

$conf['WeightFactorForSamePageName'] = 4;
$conf['WeightFactorForStartPage'] = 3;
$conf['WeightFactorForSameNamespace'] = 5;

/**
 * See {@link RouterBestEndPage::CONF_MINIMAL_SCORE_FOR_REDIRECT_DEFAULT}
 */
$conf['BestEndPageMinimalScoreForAliasCreation'] = 2;

/**
 * Does automatic canonical processing is on
 * {@link Canonical::CONF_CANONICAL_LAST_NAMES_COUNT}
 *
 */
$conf['MinimalNamesCountForAutomaticCanonical'] = 0;
/**
 * Does the canonical is reported as the unique name of the page
 * for google analytics
 * {@link action_plugin_combo_canonical::CONF_CANONICAL_FOR_GA_PAGE_VIEW}
 */
$conf['useCanonicalValueForGoogleAnalyticsPageView'] = 0;

/**
 * Icon Namespace
 * See {@link IconDownloader::CONF_ICONS_MEDIA_NAMESPACE}
 * See {@link IconDownloader::CONF_ICONS_MEDIA_NAMESPACE_DEFAULT}
 */
$conf['icons_namespace'] = ":combostrap:icons";

/**
 * Default library
 * See {@link IconDownloader::CONF_DEFAULT_ICON_LIBRARY}
 * See {@link IconDownloader::CONF_DEFAULT_ICON_LIBRARY_DEFAULT}
 */
$conf['defaultIconLibrary'] = "mdi";

/**
 * Css Optimization
 * See {@link action_plugin_combo_css::CONF_ENABLE_MINIMAL_FRONTEND_STYLESHEET}
 * See {@link action_plugin_combo_css::CONF_DISABLE_DOKUWIKI_STYLESHEET}
 */
$conf['enableMinimalFrontEndStylesheet'] = 0;
$conf['disableDokuwikiStylesheet'] = 0;

/**
 * Metadata Viewer
 * See {@link \ComboStrap\MetadataUtility::CONF_METADATA_DEFAULT_ATTRIBUTES
 * See {@link \ComboStrap\MetadataUtility::EXCLUDE_ATTRIBUTE
 */
$conf['metadataViewerDefaultAttributes'] = 'title="Metadata" exclude="tableofcontents"';

/**
 * Badge
 * See {@link syntax_plugin_combo_badge::CONF_DEFAULT_ATTRIBUTES_KEY
 */
$conf['defaultBadgeAttributes'] = 'type="info" rounded="true"';

/**
 * Ads
 * See {@link \ComboStrap\Tag\AdTag::CONF_IN_ARTICLE_PLACEHOLDER
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
 * Low Quality Page Protection
 * See {@link \ComboStrap\LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_ENABLE}
 * See {@link \ComboStrap\LowQualityPage::CONF_LOW_QUALITY_PAGE_PROTECTION_MODE}
 * See {@link \ComboStrap\LowQualityPage::CONF_LOW_QUALITY_PAGE_LINK_TYPE}
 */
$conf['lowQualityPageProtectionEnable'] = 0;
$conf['lowQualityPageProtectionMode'] = "robot";
$conf['lowQualityPageLinkType'] = "normal";


/**
 * Preformatted mode disable
 * See {@link syntax_plugin_combo_preformatted::CONF_PREFORMATTED_ENABLE}
 * See {@link syntax_plugin_combo_preformatted::CONF_PREFORMATTED_EMPTY_CONTENT_NOT_PRINTED_ENABLE}
 */
$conf['preformattedEnable'] = 1;
$conf['preformattedEmptyContentNotPrintedEnable'] = 1;

/**
 * {@link renderer_plugin_combo_analytics::CONF_MANDATORY_QUALITY_RULES}
 */
$conf['mandatoryQualityRules'] = 'words_min,internal_backlinks_min,internal_links_min';


/**
 * {@link QualityMessageHandler::CONF_EXCLUDED_QUALITY_RULES_FROM_DYNAMIC_MONITORING}
 * {@link QualityMessageHandler::CONF_DISABLE_QUALITY_MONITORING}
 */
$conf['excludedQualityRulesFromDynamicMonitoring'] = 'words_by_section_avg_min,words_by_section_avg_max';
$conf['disableDynamicQualityMonitoring'] = 0;

/**
 * Link
 * Class in link {@link \ComboStrap\LinkMarkup::CONF_USE_DOKUWIKI_CLASS_NAME}
 * Preview on link {@link \ComboStrap\LinkMarkup::CONF_PREVIEW_LINK}
 * Enable {@link syntax_plugin_combo_link::CONF_DISABLE_LINK}
 */
$conf['useDokuwikiLinkClassName'] = 0;
$conf['disableLink'] = 0;
$conf['previewLink'] = 0;


$conf['twitterSiteHandle'] = "";
$conf['twitterSiteId'] = "";
$conf['twitter:dnt'] = "on";
/**
 * {@link \ComboStrap\BlockquoteTag::CONF_TWEET_WIDGETS_THEME_DEFAULT}
 */
$conf['twitter:widgets:theme'] = "light";
/**
 * {@link \ComboStrap\BlockquoteTag::CONF_TWEET_WIDGETS_BORDER_DEFAULT}
 */
$conf['twitter:widgets:border-color'] = "#55acee";


/**
 * Facebook
 * {@link action_plugin_combo_metafacebook::CONF_DEFAULT_FACEBOOK_IMAGE}
 */
$conf['defaultFacebookImage'] = ":logo-facebook.png";

/**
 * Country
 * {@link Region::CONF_SITE_LANGUAGE_REGION}
 */
$conf['siteLanguageRegion'] = "";

/**
 *
 * See {@link \ComboStrap\PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_ENABLE}
 * See {@link \ComboStrap\PagePublicationDate::CONF_LATE_PUBLICATION_PROTECTION_MODE}
 */
$conf['latePublicationProtectionEnable'] = 1;
$conf["latePublicationProtectionMode"] = "acl";

/**
 * Default page type
 * {@link PageType::CONF_DEFAULT_PAGE_TYPE}
 */
$conf["defaultPageType"] = "article";

/**
 * Default shadow elevation
 * {@link \ComboStrap\TagAttribute\Shadow::CONF_DEFAULT_VALUE}
 */
$conf["defaultShadowLevel"] = "medium";


/**
 * Lazy loading {@link \ComboStrap\SvgImageLink::CONF_LAZY_LOAD_ENABLE}
 */
$conf["svgLazyLoadEnable"] = 1;

/**
 * Injection {@link \ComboStrap\SvgImageLink::CONF_SVG_INJECTION_ENABLE}
 */
$conf["svgInjectionEnable"] = 0;

/**
 * Svg Optimization Disable {@link \ComboStrap\SvgDocument::CONF_SVG_OPTIMIZATION_ENABLE}
 */
$conf["svgOptimizationEnable"] = 1;


/**
 * The name of the group of user that can upload svg
 * {@link Identity::CONF_DESIGNER_GROUP_NAME}
 */
$conf["combo-conf-006"] = "";

/**
 * Svg Optimization
 * {@link \ComboStrap\SvgDocument::CONF_OPTIMIZATION_NAMESPACES_TO_KEEP}
 * The attribute to delete separates by a ,
 */
$conf["svgOptimizationNamespacesToKeep"] = "";

/**
 * Svg Optimization
 * {@link \ComboStrap\SvgDocument::CONF_OPTIMIZATION_ATTRIBUTES_TO_DELETE}
 * The attribute to delete separates by a ,
 */
$conf["svgOptimizationAttributesToDelete"] = "id, style, class, data-name";
/**
 * {@link \ComboStrap\SvgDocument::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE}
 */
$conf["svgOptimizationElementsToDelete"] = "script, style, title, desc";
/**
 * {@link \ComboStrap\SvgDocument::CONF_OPTIMIZATION_ELEMENTS_TO_DELETE_IF_EMPTY}
 */
$conf["svgOptimizationElementsToDeleteIfEmpty"] = "metadata, defs";

/**
 * {@link \ComboStrap\SvgDocument::CONF_PRESERVE_ASPECT_RATIO_DEFAULT}
 */

$conf["svgPreserveAspectRatioDefault"] = "xMidYMid slice";

/**
 * Lazy loading {@link \ComboStrap\RasterImageLink::CONF_LAZY_LOADING_ENABLE}
 */
$conf["rasterImageLazyLoadingEnable"] = 1;

/**
 * {@link \ComboStrap\RasterImageLink::CONF_RESPONSIVE_IMAGE_MARGIN}
 */
$conf["responsiveImageMargin"] = "20px";

/**
 * {@link \ComboStrap\RasterImageLink::CONF_RETINA_SUPPORT_ENABLED}
 */
$conf["retinaRasterImageEnable"] = 0;

/**
 * {@link \ComboStrap\LazyLoad::CONF_LAZY_LOADING_PLACEHOLDER_COLOR
 */
$conf["lazyLoadingPlaceholderColor"] = "#cbf1ea";


/**
 * {@link \ComboStrap\MediaLink::CONF_IMAGE_ENABLE}
 */
$conf["imageEnable"] = 1;

/**
 * Default linking value
 * {@link \ComboStrap\MediaLink::CONF_DEFAULT_LINKING}
 */
$conf["defaultImageLinking"] = "direct";

/**
 * Float
 *  {@link \ComboStrap\FloatAttribute::CONF_FLOAT_DEFAULT_BREAKPOINT}
 */
$conf["floatDefaultBreakpoint"] = "sm";

/**
 * Outline Numbering
 * {@link Outline::CONF_OUTLINE_NUMBERING_ENABLE}
 * {@link Outline::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL2}
 * {@link Outline::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL3}
 * {@link Outline::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL4}
 * {@link Outline::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL5}
 * {@link Outline::CONF_OUTLINE_NUMBERING_COUNTER_STYLE_LEVEL6}
 * {@link Outline::CONF_OUTLINE_NUMBERING_COUNTER_SEPARATOR}
 * {@link Outline::CONF_OUTLINE_NUMBERING_PREFIX}
 * {@link Outline::CONF_OUTLINE_NUMBERING_SUFFIX}
 */
$conf["outlineNumberingEnable"] = 1;
$conf["outlineNumberingCounterStyleLevel2"] = "decimal";
$conf["outlineNumberingCounterStyleLevel3"] = "decimal";
$conf["outlineNumberingCounterStyleLevel4"] = "decimal";
$conf["outlineNumberingCounterStyleLevel5"] = "decimal";
$conf["outlineNumberingCounterStyleLevel6"] = "decimal";
$conf["outlineNumberingCounterSeparator"] = ".";
$conf["outlineNumberingPrefix"] = "";
$conf["outlineNumberingSuffix"] = " - ";

/**
 * Form
 * {@link \ComboStrap\Identity::CONF_ENABLE_LOGO_ON_IDENTITY_FORMS}
 * {@link action_plugin_combo_login::CONF_ENABLE_LOGIN_FORM }
 * {@link action_plugin_combo_registration::CONF_ENABLE_REGISTER_FORM }
 * {@link action_plugin_combo_resend::CONF_ENABLE_RESEND_PWD_FORM }
 * {@link action_plugin_combo_profile::CONF_ENABLE_PROFILE_UPDATE_FORM }
 * {@link action_plugin_combo_profile::CONF_ENABLE_PROFILE_DELETE_FORM }
 */
$conf["enableLogoOnIdentityForms"] = 1;
$conf["enableLoginForm"] = 1;
$conf["enableRegistrationForm"] = 1;
$conf["enableResendPwdForm"] = 1;
$conf["enableProfileUpdateForm"] = 1;
$conf["enableProfileDeleteForm"] = 1;

/**
 * {@link syntax_plugin_combo_comment::CONF_OUTPUT_COMMENT}
 */
$conf['outputComment'] = 0;

/**
 * {@link action_plugin_combo_staticresource::CONF_STATIC_CACHE_ENABLED}
 */
$conf["staticCacheEnabled"] = 1;


/**
 * {@link action_plugin_combo_linkwizard::CONF_ENABLE_ENHANCED_LINK_WIZARD}
 */
$conf["enableEnhancedLinkWizard"] = 1;

/**
 * {@link PageUrlType::CONF_CANONICAL_URL_TYPE}
 * {@link PageUrlType::CONF_CANONICAL_URL_TYPE_DEFAULT}
 */
$conf["pageUrlType"] = "page path";

/**
 * {@link MetadataFrontmatterStore::CONF_ENABLE_FRONT_MATTER_ON_SUBMIT}
 * {@link syntax_plugin_combo_frontmatter::CONF_ENABLE_FRONT_MATTER_ON_SUBMIT_DEFAULT}
 */
$conf["enableFrontMatterOnSubmit"] = 0;

/**
 * {@link syntax_plugin_combo_headingwiki::CONF_WIKI_HEADING_ENABLE} and
 * {@link syntax_plugin_combo_headingwiki::CONF_DEFAULT_WIKI_ENABLE_VALUE}
 */
$conf["headingWikiEnable"] = 1;
/**
 * Highlight
 * {@link syntax_plugin_combo_highlightwiki::CONF_HIGHLIGHT_WIKI_ENABLE}
 * {@link syntax_plugin_combo_highlightwiki::CONF_DEFAULT_HIGHLIGHT_WIKI_ENABLE_VALUE}
 */
$conf["highlightWikiEnable"] = 1;

/**
 * {@link \ComboStrap\ColorRgb::BRANDING_COLOR_INHERITANCE_ENABLE_CONF}
 */
$conf["brandingColorInheritanceEnable"] = 1;

/**
 * {@link \ComboStrap\ColorRgb::PRIMARY_COLOR_CONF}
 * {@link \ComboStrap\ColorRgb::SECONDARY_COLOR_CONF}
 */
$conf["primaryColor"] = "";
$conf["secondaryColor"] = "";

/**
 * {@link ContainerTag::DEFAULT_LAYOUT_CONTAINER_CONF}
 */
$conf["defaultLayoutContainer"] = "sm";

/**
 * Enable templating
 * See {@link SiteConfig::CONF_ENABLE_THEME_SYSTEM}
 */
$conf["combo-conf-001"] = 1;


/**
 * CDN for library ?
 * See {@link Snippet::CONF_USE_CDN}
 */
$conf['useCDN'] = 1;

/**
 * {@link Bootstrap::CONF_BOOTSTRAP_VERSION_STYLESHEET}
 */
$conf["bootstrapVersionStylesheet"] = "5.0.1 - bootstrap";

/**
 * {@link action_plugin_combo_snippetsbootstrap::CONF_PRELOAD_CSS}
 */
$conf['preloadCss'] = 0;

/**
 * {@link FetcherRailBar::CONF_PRIVATE_RAIL_BAR}
 */
$conf['privateRailbar'] = 0;
/**
 * {@link FetcherRailBar::CONF_BREAKPOINT_RAIL_BAR}
 */
$conf['breakpointRailbar'] = "large";

/**
 * @see {@link action_plugin_combo_snippetsbootstrap::CONF_JQUERY_DOKU}
 * @See {@link action_plugin_combo_snippetsbootstrap::CONF_DISABLE_BACKEND_JAVASCRIPT}
 */
$conf['jQueryDoku'] = 0;
$conf["disableBackendJavascript"] = 0;


/**
 * {@link \ComboStrap\SiteConfig::REM_CONF}
 */
$conf['combo-conf-002'] = 16;

/**
 * {@link SiteConfig::HTML_MAX_KB_SIZE_FOR_INLINE_ELEMENT}
 */
$conf['combo-conf-003'] = 4;

/**
 * {@link \ComboStrap\TemplateEngine::CONF_THEME}
 * {@link \ComboStrap\TemplateEngine::CONF_THEME_DEFAULT}
 */
$conf['combo-conf-005'] = 'default';

/**
 * {@link \ComboStrap\Tag\AdTag::CONF_IN_ARTICLE_ENABLED}
 */
$conf['combo-conf-007'] = 0;

/**
 * {@link \ComboStrap\TemplateSlot::CONF_PAGE_HEADER_NAME
 * {@link \ComboStrap\TemplateSlot::CONF_PAGE_HEADER_NAME
 */
$conf['combo-conf-008'] = 'slot_header';
$conf['combo-conf-009'] = 'slot_footer';
